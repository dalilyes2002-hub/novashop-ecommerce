<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/catalogue.php';
require_once __DIR__ . '/config/database.php';

$stmt = $pdo->prepare(
    'SELECT c.id, c.nom, c.description, COUNT(p.id) AS nb_produits
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id AND p.actif = 1
     GROUP BY c.id, c.nom, c.description
     ORDER BY c.nom'
);
$stmt->execute();
$categories = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT p.id, p.nom, p.prix, p.stock, p.actif, p.image, c.nom AS categorie_nom
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     WHERE p.actif = 1
     ORDER BY p.stock = 0, p.id DESC
     LIMIT 4'
);
$stmt->execute();
$produitsVedette = $stmt->fetchAll();

$pageTitle = 'Accueil';
require __DIR__ . '/includes/header.php';
?>

<section class="hero row align-items-center g-4">
    <div class="col-12 col-md-7 col-lg-8">
        <h1 class="hero-title">Bienvenue sur NovaShop</h1>
        <p class="hero-text">PC, périphériques et accessoires, livrés chez toi.</p>
        <a class="btn btn-success" href="<?= e(BASE_URL) ?>/produits.php">Voir le catalogue</a>
    </div>
    <div class="col-12 col-md-5 col-lg-4">
        <div class="hero-badge">
            <span class="hero-badge-number"><?= e((string) count($categories)) ?></span>
            <span class="hero-badge-label">catégories</span>
        </div>
    </div>
</section>

<section class="mt-4 mt-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Catégories</h2>
        <a class="small" href="<?= e(BASE_URL) ?>/categories.php">Toutes les catégories</a>
    </div>
    <div class="row g-3 g-lg-4">
        <?php foreach ($categories as $categorie): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a class="info-card d-block text-decoration-none text-reset" href="<?= e(BASE_URL) ?>/produits.php?category_id=<?= e((string) $categorie['id']) ?>">
                    <h3 class="h5"><?= e($categorie['nom']) ?></h3>
                    <p class="mb-1 text-muted small"><?= e((string) $categorie['description']) ?></p>
                    <p class="mb-0 small"><?= e((string) $categorie['nb_produits']) ?> produit(s)</p>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-4 mt-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Nos produits</h2>
        <a class="small" href="<?= e(BASE_URL) ?>/produits.php">Tout voir</a>
    </div>
    <div class="row g-3 g-lg-4">
        <?php foreach ($produitsVedette as $produit): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <?php require __DIR__ . '/includes/product_card.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
