<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/catalogue.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$orderId = (int) ($_GET['id'] ?? 0);

// Le filtre user_id empêche de lire la commande de quelqu'un d'autre en changeant l'id dans l'URL.
$stmt = $pdo->prepare(
    'SELECT id, total, adresse_livraison, mode_paiement, statut, created_at
     FROM orders
     WHERE id = ? AND user_id = ?
     LIMIT 1'
);
$stmt->execute([$orderId, $userId]);
$commande = $stmt->fetch();

if (!$commande) {
    http_response_code(404);
    $pageTitle = 'Commande introuvable';
    require __DIR__ . '/includes/header.php';
    ?>
    <h1 class="h3 mb-3">Commande introuvable</h1>
    <p>Cette commande n’existe pas ou n’est pas la tienne.</p>
    <a class="btn btn-success" href="<?= e(BASE_URL) ?>/commandes.php">Mes commandes</a>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Le nom du produit est repris de products, mais le prix vient d'order_items :
// c'est le prix au moment de l'achat, même si le catalogue a changé depuis.
$stmt = $pdo->prepare(
    'SELECT oi.quantite, oi.prix_unitaire, oi.product_id, p.nom
     FROM order_items oi
     INNER JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?
     ORDER BY p.nom'
);
$stmt->execute([$orderId]);
$lignes = $stmt->fetchAll();

$pageTitle = 'Commande n°' . $commande['id'];
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-1">Commande n°<?= e((string) $commande['id']) ?></h1>
<p class="text-muted">
    Passée le <?= e(date('d/m/Y à H:i', strtotime((string) $commande['created_at']))) ?>
</p>

<div class="row g-3 g-lg-4 mb-3">
    <div class="col-12 col-md-4">
        <article class="info-card">
            <h2 class="h6">Statut</h2>
            <p class="mb-0"><?= e(libelleStatut((string) $commande['statut'])) ?></p>
        </article>
    </div>
    <div class="col-12 col-md-4">
        <article class="info-card">
            <h2 class="h6">Paiement</h2>
            <p class="mb-0"><?= e(libellePaiement((string) $commande['mode_paiement'])) ?></p>
        </article>
    </div>
    <div class="col-12 col-md-4">
        <article class="info-card">
            <h2 class="h6">Livraison</h2>
            <p class="mb-0"><?= nl2br(e((string) $commande['adresse_livraison'])) ?></p>
        </article>
    </div>
</div>

<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th scope="col">Produit</th>
                <th scope="col">Prix payé</th>
                <th scope="col">Quantité</th>
                <th scope="col">Sous-total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $ligne): ?>
                <tr>
                    <td>
                        <a href="<?= e(urlProduit((int) $ligne['product_id'])) ?>"><?= e($ligne['nom']) ?></a>
                    </td>
                    <td><?= e(formatPrix($ligne['prix_unitaire'])) ?></td>
                    <td><?= e((string) $ligne['quantite']) ?></td>
                    <td><?= e(formatPrix((float) $ligne['prix_unitaire'] * (int) $ligne['quantite'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total</th>
                <th><?= e(formatPrix($commande['total'])) ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<a class="btn btn-outline-secondary" href="<?= e(BASE_URL) ?>/commandes.php">Retour à mes commandes</a>

<?php require __DIR__ . '/includes/footer.php'; ?>
