<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/catalogue.php';
require_once __DIR__ . '/config/database.php';

$stmt = $pdo->prepare(
    'SELECT c.id, c.nom, c.description,
            COUNT(p.id) AS nb_produits,
            SUM(CASE WHEN p.stock > 0 THEN 1 ELSE 0 END) AS nb_en_stock
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id AND p.actif = 1
     GROUP BY c.id, c.nom, c.description
     ORDER BY c.nom'
);
$stmt->execute();
$categories = $stmt->fetchAll();

$pageTitle = 'Catégories';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-3">Catégories</h1>

<div class="row g-3 g-lg-4">
    <?php foreach ($categories as $categorie): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <article class="info-card">
                <h2 class="h5"><?= e($categorie['nom']) ?></h2>
                <p class="text-muted small"><?= e((string) $categorie['description']) ?></p>
                <p class="mb-3 small">
                    <?= e((string) $categorie['nb_produits']) ?> produit(s),
                    dont <?= e((string) (int) $categorie['nb_en_stock']) ?> en stock
                </p>
                <a class="btn btn-outline-success btn-sm" href="<?= e(BASE_URL) ?>/produits.php?category_id=<?= e((string) $categorie['id']) ?>">
                    Voir les produits
                </a>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
