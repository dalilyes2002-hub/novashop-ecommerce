<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT id, prenom, nom, email, telephone, adresse, role, created_at
     FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    http_response_code(404);
    $pageTitle = 'Client introuvable';
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/nav.php';
    ?>
    <h1 class="h3 mb-3">Client introuvable</h1>
    <a class="btn btn-success" href="<?= e(ADMIN_URL) ?>/clients.php">Retour à la liste</a>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT o.id, o.total, o.statut, o.mode_paiement, o.created_at, COUNT(oi.id) AS nb_lignes
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY o.id, o.total, o.statut, o.mode_paiement, o.created_at
     ORDER BY o.created_at DESC, o.id DESC'
);
$stmt->execute([$id]);
$commandes = $stmt->fetchAll();

$pageTitle = 'Client ' . $client['prenom'] . ' ' . $client['nom'];
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<h1 class="h3 mb-3"><?= e($client['prenom'] . ' ' . $client['nom']) ?></h1>

<div class="row g-3 g-lg-4 mb-4">
    <div class="col-12 col-md-4">
        <article class="info-card">
            <h2 class="h6">Contact</h2>
            <p class="mb-1"><?= e($client['email']) ?></p>
            <p class="mb-0"><?= e($client['telephone']) ?></p>
        </article>
    </div>
    <div class="col-12 col-md-4">
        <article class="info-card">
            <h2 class="h6">Adresse</h2>
            <p class="mb-0"><?= nl2br(e((string) $client['adresse'])) ?></p>
        </article>
    </div>
    <div class="col-12 col-md-4">
        <article class="info-card">
            <h2 class="h6">Compte</h2>
            <p class="mb-1">Rôle : <?= e($client['role']) ?></p>
            <p class="mb-0">
                Inscrit le <?= e(date('d/m/Y à H:i', strtotime((string) $client['created_at']))) ?>
            </p>
        </article>
    </div>
</div>

<h2 class="h5 mb-3">Commandes (<?= e((string) count($commandes)) ?>)</h2>

<?php if ($commandes === []): ?>
    <div class="alert alert-secondary">Ce client n’a pas encore commandé.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">N°</th>
                    <th scope="col">Date</th>
                    <th scope="col">Articles</th>
                    <th scope="col">Total</th>
                    <th scope="col">Statut</th>
                    <th scope="col"><span class="visually-hidden">Détail</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $commande): ?>
                    <tr>
                        <td><?= e((string) $commande['id']) ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime((string) $commande['created_at']))) ?></td>
                        <td><?= e((string) $commande['nb_lignes']) ?></td>
                        <td><?= e(formatPrix($commande['total'])) ?></td>
                        <td><?= e(libelleStatut((string) $commande['statut'])) ?></td>
                        <td class="text-end">
                            <a class="btn btn-outline-success btn-sm"
                               href="<?= e(ADMIN_URL) ?>/commande.php?id=<?= e((string) $commande['id']) ?>">
                                Détail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<a class="btn btn-outline-secondary" href="<?= e(ADMIN_URL) ?>/clients.php">Retour à la liste</a>

<?php require __DIR__ . '/../includes/footer.php'; ?>
