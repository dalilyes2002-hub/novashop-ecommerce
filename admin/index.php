<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$compteurs = [];

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'");
$compteurs['clients'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM products');
$compteurs['produits'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM products WHERE actif = 1');
$compteurs['produits_actifs'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM products WHERE stock = 0');
$compteurs['ruptures'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM categories');
$compteurs['categories'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) FROM orders');
$compteurs['commandes'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE statut = 'en_attente'");
$compteurs['commandes_attente'] = (int) $stmt->fetchColumn();

// Chiffre d'affaires : on ne compte pas les commandes annulées.
$stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE statut != 'annulee'");
$compteurs['ca'] = (float) $stmt->fetchColumn();

$stmt = $pdo->query(
    "SELECT o.id, o.total, o.statut, o.created_at, u.prenom, u.nom
     FROM orders o
     INNER JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC, o.id DESC
     LIMIT 5"
);
$dernieresCommandes = $stmt->fetchAll();

$stmt = $pdo->query(
    'SELECT id, nom, stock, actif FROM products WHERE stock <= 5 ORDER BY stock, nom LIMIT 5'
);
$stocksFaibles = $stmt->fetchAll();

$pageTitle = 'Administration';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<h1 class="h3 mb-3">Dashboard</h1>

<div class="row g-3 g-lg-4">
    <div class="col-6 col-lg-3">
        <article class="info-card">
            <h2 class="h6 text-muted">Clients</h2>
            <p class="stat-number mb-0"><?= e((string) $compteurs['clients']) ?></p>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="info-card">
            <h2 class="h6 text-muted">Produits</h2>
            <p class="stat-number mb-0"><?= e((string) $compteurs['produits']) ?></p>
            <p class="small text-muted mb-0"><?= e((string) $compteurs['produits_actifs']) ?> actif(s)</p>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="info-card">
            <h2 class="h6 text-muted">Commandes</h2>
            <p class="stat-number mb-0"><?= e((string) $compteurs['commandes']) ?></p>
            <p class="small text-muted mb-0"><?= e((string) $compteurs['commandes_attente']) ?> en attente</p>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="info-card">
            <h2 class="h6 text-muted">Chiffre d’affaires</h2>
            <p class="stat-number mb-0"><?= e(formatPrix($compteurs['ca'])) ?></p>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="info-card">
            <h2 class="h6 text-muted">Catégories</h2>
            <p class="stat-number mb-0"><?= e((string) $compteurs['categories']) ?></p>
        </article>
    </div>
    <div class="col-6 col-lg-3">
        <article class="info-card">
            <h2 class="h6 text-muted">Ruptures de stock</h2>
            <p class="stat-number mb-0 <?= $compteurs['ruptures'] > 0 ? 'text-danger' : '' ?>">
                <?= e((string) $compteurs['ruptures']) ?>
            </p>
        </article>
    </div>
</div>

<div class="row g-3 g-lg-4 mt-1">
    <div class="col-12 col-lg-7">
        <h2 class="h5 mt-3 mb-3">Dernières commandes</h2>
        <?php if ($dernieresCommandes === []): ?>
            <div class="alert alert-secondary">Aucune commande.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">N°</th>
                            <th scope="col">Client</th>
                            <th scope="col">Total</th>
                            <th scope="col">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dernieresCommandes as $commande): ?>
                            <tr>
                                <td>
                                    <a href="<?= e(ADMIN_URL) ?>/commande.php?id=<?= e((string) $commande['id']) ?>">
                                        <?= e((string) $commande['id']) ?>
                                    </a>
                                </td>
                                <td><?= e($commande['prenom'] . ' ' . $commande['nom']) ?></td>
                                <td><?= e(formatPrix($commande['total'])) ?></td>
                                <td><?= e(libelleStatut((string) $commande['statut'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-5">
        <h2 class="h5 mt-3 mb-3">Stocks faibles</h2>
        <?php if ($stocksFaibles === []): ?>
            <div class="alert alert-secondary">Aucun stock critique.</div>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($stocksFaibles as $produit): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <?= e($produit['nom']) ?>
                            <?php if ((int) $produit['actif'] === 0): ?>
                                <span class="badge bg-secondary">inactif</span>
                            <?php endif; ?>
                        </span>
                        <span class="badge <?= (int) $produit['stock'] === 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                            <?= e((string) $produit['stock']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
