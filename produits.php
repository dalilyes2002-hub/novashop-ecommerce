<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/catalogue.php';
require_once __DIR__ . '/config/database.php';

$q = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category_id'] ?? 0);
$prixMinBrut = trim((string) ($_GET['prix_min'] ?? ''));
$prixMaxBrut = trim((string) ($_GET['prix_max'] ?? ''));
$enStock = isset($_GET['en_stock']);
$tri = (string) ($_GET['tri'] ?? 'nom');

$prixMin = is_numeric($prixMinBrut) ? (float) $prixMinBrut : null;
$prixMax = is_numeric($prixMaxBrut) ? (float) $prixMaxBrut : null;

$trisPossibles = [
    'nom' => 'p.nom ASC',
    'prix_asc' => 'p.prix ASC',
    'prix_desc' => 'p.prix DESC',
];
if (!isset($trisPossibles[$tri])) {
    $tri = 'nom';
}

$stmt = $pdo->prepare('SELECT id, nom FROM categories ORDER BY nom');
$stmt->execute();
$categories = $stmt->fetchAll();

$conditions = ['p.actif = 1'];
$params = [];

if ($q !== '') {
    $conditions[] = '(p.nom LIKE ? OR p.description LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

if ($categoryId > 0) {
    $conditions[] = 'p.category_id = ?';
    $params[] = $categoryId;
}

if ($prixMin !== null) {
    $conditions[] = 'p.prix >= ?';
    $params[] = $prixMin;
}

if ($prixMax !== null) {
    $conditions[] = 'p.prix <= ?';
    $params[] = $prixMax;
}

if ($enStock) {
    $conditions[] = 'p.stock > 0';
}

$sql = 'SELECT p.id, p.nom, p.prix, p.stock, p.actif, p.image, c.nom AS categorie_nom
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE ' . implode(' AND ', $conditions) . '
        ORDER BY ' . $trisPossibles[$tri];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll();

$pageTitle = 'Produits';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-3">Nos produits</h1>

<form class="filters mb-4" method="get" action="<?= e(BASE_URL) ?>/produits.php">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label" for="q">Recherche</label>
            <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nom du produit">
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label" for="category_id">Catégorie</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="0">Toutes</option>
                <?php foreach ($categories as $categorie): ?>
                    <option value="<?= e((string) $categorie['id']) ?>" <?= $categoryId === (int) $categorie['id'] ? 'selected' : '' ?>>
                        <?= e($categorie['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label" for="prix_min">Prix min</label>
            <input class="form-control" type="number" step="0.01" min="0" id="prix_min" name="prix_min" value="<?= e($prixMinBrut) ?>">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label" for="prix_max">Prix max</label>
            <input class="form-control" type="number" step="0.01" min="0" id="prix_max" name="prix_max" value="<?= e($prixMaxBrut) ?>">
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label" for="tri">Trier par</label>
            <select class="form-select" id="tri" name="tri">
                <option value="nom" <?= $tri === 'nom' ? 'selected' : '' ?>>Nom (A-Z)</option>
                <option value="prix_asc" <?= $tri === 'prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
                <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="en_stock" name="en_stock" value="1" <?= $enStock ? 'checked' : '' ?>>
                <label class="form-check-label" for="en_stock">Disponibles seulement</label>
            </div>
        </div>
        <div class="col-12 col-lg-6 d-flex gap-2">
            <button class="btn btn-success" type="submit">Filtrer</button>
            <a class="btn btn-outline-secondary" href="<?= e(BASE_URL) ?>/produits.php">Réinitialiser</a>
        </div>
    </div>
</form>

<p class="text-muted"><?= e((string) count($produits)) ?> produit(s) trouvé(s)</p>

<?php if ($produits === []): ?>
    <div class="alert alert-secondary">Aucun produit ne correspond à ta recherche.</div>
<?php else: ?>
    <div class="row g-3 g-lg-4">
        <?php foreach ($produits as $produit): ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <?php require __DIR__ . '/includes/product_card.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
