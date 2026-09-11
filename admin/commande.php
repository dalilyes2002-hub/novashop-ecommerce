<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $orderId = (int) ($_POST['id'] ?? 0);
    $statut = (string) ($_POST['statut'] ?? '');

    $message = changerStatutCommande($pdo, $orderId, $statut);

    if ($message === '') {
        setFlash('success', 'Statut mis à jour.');
    } else {
        setFlash('danger', $message);
    }

    redirect('/admin/commande.php?id=' . $orderId);
}

$orderId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT o.id, o.total, o.adresse_livraison, o.mode_paiement, o.statut, o.created_at,
            u.id AS client_id, u.prenom, u.nom, u.email, u.telephone
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     WHERE o.id = ?
     LIMIT 1'
);
$stmt->execute([$orderId]);
$commande = $stmt->fetch();

if (!$commande) {
    http_response_code(404);
    $pageTitle = 'Commande introuvable';
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/nav.php';
    ?>
    <h1 class="h3 mb-3">Commande introuvable</h1>
    <a class="btn btn-success" href="<?= e(ADMIN_URL) ?>/commandes.php">Retour aux commandes</a>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

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
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<h1 class="h3 mb-1">Commande n°<?= e((string) $commande['id']) ?></h1>
<p class="text-muted">
    Passée le <?= e(date('d/m/Y à H:i', strtotime((string) $commande['created_at']))) ?>
</p>

<div class="row g-3 g-lg-4 mb-3">
    <div class="col-12 col-md-4">
        <article class="info-card">
            <h2 class="h6">Client</h2>
            <p class="mb-1">
                <a href="<?= e(ADMIN_URL) ?>/client.php?id=<?= e((string) $commande['client_id']) ?>">
                    <?= e($commande['prenom'] . ' ' . $commande['nom']) ?>
                </a>
            </p>
            <p class="mb-1 small"><?= e($commande['email']) ?></p>
            <p class="mb-0 small"><?= e($commande['telephone']) ?></p>
        </article>
    </div>
    <div class="col-12 col-md-4">
        <article class="info-card">
            <h2 class="h6">Livraison</h2>
            <p class="mb-0"><?= nl2br(e((string) $commande['adresse_livraison'])) ?></p>
        </article>
    </div>
    <div class="col-12 col-md-4">
        <article class="info-card">
            <h2 class="h6">Paiement</h2>
            <p class="mb-0"><?= e(libellePaiement((string) $commande['mode_paiement'])) ?></p>
        </article>
    </div>
</div>

<div class="info-card mb-4">
    <h2 class="h6">Statut</h2>
    <form class="row g-2 align-items-end" method="post" action="<?= e(ADMIN_URL) ?>/commande.php">
        <?= champCsrf() ?>
        <input type="hidden" name="id" value="<?= e((string) $commande['id']) ?>">
        <div class="col-12 col-sm-6 col-md-4">
            <label class="form-label" for="statut">Changer le statut</label>
            <select class="form-select" id="statut" name="statut">
                <?php foreach (statutsCommande() as $statut): ?>
                    <option value="<?= e($statut) ?>" <?= $commande['statut'] === $statut ? 'selected' : '' ?>>
                        <?= e(libelleStatut($statut)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-success" type="submit">Enregistrer</button>
        </div>
    </form>
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
                        <a href="<?= e(ADMIN_URL) ?>/produit-form.php?id=<?= e((string) $ligne['product_id']) ?>">
                            <?= e($ligne['nom']) ?>
                        </a>
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

<a class="btn btn-outline-secondary" href="<?= e(ADMIN_URL) ?>/commandes.php">Retour aux commandes</a>

<?php require __DIR__ . '/../includes/footer.php'; ?>
