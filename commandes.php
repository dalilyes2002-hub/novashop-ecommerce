<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/catalogue.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT o.id, o.total, o.statut, o.mode_paiement, o.created_at,
            COUNT(oi.id) AS nb_lignes
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY o.id, o.total, o.statut, o.mode_paiement, o.created_at
     ORDER BY o.created_at DESC, o.id DESC'
);
$stmt->execute([$userId]);
$commandes = $stmt->fetchAll();

$pageTitle = 'Mes commandes';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-3">Mes commandes</h1>

<?php if ($commandes === []): ?>
    <div class="alert alert-secondary">Tu n’as pas encore passé de commande.</div>
    <a class="btn btn-success" href="<?= e(BASE_URL) ?>/produits.php">Voir le catalogue</a>
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
                            <a class="btn btn-outline-success btn-sm" href="<?= e(BASE_URL) ?>/commande.php?id=<?= e((string) $commande['id']) ?>">
                                Détail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
