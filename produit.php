<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/catalogue.php';
require_once __DIR__ . '/config/database.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT p.id, p.nom, p.description, p.prix, p.stock, p.actif, p.image,
            p.category_id, c.nom AS categorie_nom
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     WHERE p.id = ? AND p.actif = 1
     LIMIT 1'
);
$stmt->execute([$id]);
$produit = $stmt->fetch();

if (!$produit) {
    http_response_code(404);
    $pageTitle = 'Produit introuvable';
    require __DIR__ . '/includes/header.php';
    ?>
    <h1 class="h3 mb-3">Produit introuvable</h1>
    <p>Ce produit n’existe pas ou n’est plus au catalogue.</p>
    <a class="btn btn-success" href="<?= e(BASE_URL) ?>/produits.php">Retour au catalogue</a>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare(
    'SELECT p.id, p.nom, p.prix, p.stock, p.actif, p.image, c.nom AS categorie_nom
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     WHERE p.actif = 1 AND p.category_id = ? AND p.id != ?
     ORDER BY p.nom
     LIMIT 3'
);
$stmt->execute([(int) $produit['category_id'], (int) $produit['id']]);
$similaires = $stmt->fetchAll();

$image = imageProduit($produit);
$disponible = estDisponible($produit);

$pageTitle = $produit['nom'];
require __DIR__ . '/includes/header.php';
?>

<nav aria-label="Fil d’Ariane" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>/index.php">Accueil</a></li>
        <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>/produits.php">Produits</a></li>
        <li class="breadcrumb-item">
            <a href="<?= e(BASE_URL) ?>/produits.php?category_id=<?= e((string) $produit['category_id']) ?>">
                <?= e($produit['categorie_nom']) ?>
            </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($produit['nom']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-12 col-md-6">
        <?php if ($image !== null): ?>
            <img class="product-detail-image" src="<?= e($image) ?>" alt="<?= e($produit['nom']) ?>">
        <?php else: ?>
            <div class="product-detail-image product-thumb-empty">Pas d’image</div>
        <?php endif; ?>
    </div>
    <div class="col-12 col-md-6">
        <p class="product-category mb-1"><?= e($produit['categorie_nom']) ?></p>
        <h1 class="h3"><?= e($produit['nom']) ?></h1>
        <p class="h4 text-success"><?= e(formatPrix($produit['prix'])) ?></p>

        <?php if ($disponible): ?>
            <p class="text-success">En stock : <?= e((string) $produit['stock']) ?> disponible(s)</p>
        <?php else: ?>
            <p class="text-danger">Rupture de stock</p>
        <?php endif; ?>

        <p><?= nl2br(e((string) $produit['description'])) ?></p>

        <?php if ($disponible): ?>
            <form class="row g-2 align-items-end" method="post" action="<?= e(BASE_URL) ?>/panier.php">
                <input type="hidden" name="action" value="ajouter">
                <input type="hidden" name="product_id" value="<?= e((string) $produit['id']) ?>">
                <div class="col-auto">
                    <label class="form-label" for="quantite">Quantité</label>
                    <input class="form-control cart-qty" type="number" id="quantite" name="quantite"
                           min="1" max="<?= e((string) min((int) $produit['stock'], PANIER_QUANTITE_MAX)) ?>" value="1">
                </div>
                <div class="col-auto">
                    <button class="btn btn-success" type="submit">Ajouter au panier</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-secondary mb-0">Ce produit n’est pas commandable pour le moment.</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($similaires !== []): ?>
    <section class="mt-4 mt-lg-5">
        <h2 class="h4 mb-3">Dans la même catégorie</h2>
        <div class="row g-3 g-lg-4">
            <?php foreach ($similaires as $produit): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <?php require __DIR__ . '/includes/product_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
