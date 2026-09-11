<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'basculer_actif') {
        $stmt = $pdo->prepare('UPDATE products SET actif = 1 - actif WHERE id = ?');
        $stmt->execute([$id]);
        setFlash('success', 'Visibilité du produit mise à jour.');
    }

    if ($action === 'stock') {
        $stock = (int) ($_POST['stock'] ?? 0);
        if ($stock < 0) {
            setFlash('danger', 'Le stock ne peut pas être négatif.');
        } else {
            $stmt = $pdo->prepare('UPDATE products SET stock = ? WHERE id = ?');
            $stmt->execute([$stock, $id]);
            setFlash('success', 'Stock mis à jour.');
        }
    }

    if ($action === 'supprimer') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
        $stmt->execute([$id]);
        $nbLignes = (int) $stmt->fetchColumn();

        if ($nbLignes > 0) {
            setFlash('danger', 'Suppression refusée : ce produit figure dans ' . $nbLignes . ' commande(s). Désactive-le à la place.');
        } else {
            $stmt = $pdo->prepare('SELECT image FROM products WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $image = $stmt->fetchColumn();

            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);
            supprimerImageProduit($image === false ? null : (string) $image);
            setFlash('success', 'Produit supprimé.');
        }
    }

    redirect('/admin/produits.php');
}

$categoryId = (int) ($_GET['category_id'] ?? 0);
$etat = (string) ($_GET['etat'] ?? 'tous');

$conditions = [];
$params = [];

if ($categoryId > 0) {
    $conditions[] = 'p.category_id = ?';
    $params[] = $categoryId;
}
if ($etat === 'actifs') {
    $conditions[] = 'p.actif = 1';
} elseif ($etat === 'inactifs') {
    $conditions[] = 'p.actif = 0';
} elseif ($etat === 'ruptures') {
    $conditions[] = 'p.stock = 0';
}

$sql = 'SELECT p.id, p.nom, p.prix, p.stock, p.actif, p.image, c.nom AS categorie_nom
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id';
if ($conditions !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' ORDER BY p.nom';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll();

$stmt = $pdo->query('SELECT id, nom FROM categories ORDER BY nom');
$categories = $stmt->fetchAll();

$pageTitle = 'Produits (admin)';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Produits</h1>
    <a class="btn btn-success" href="<?= e(ADMIN_URL) ?>/produit-form.php">Nouveau produit</a>
</div>

<form class="filters mb-4" method="get" action="<?= e(ADMIN_URL) ?>/produits.php">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-5">
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
        <div class="col-12 col-md-4">
            <label class="form-label" for="etat">État</label>
            <select class="form-select" id="etat" name="etat">
                <option value="tous" <?= $etat === 'tous' ? 'selected' : '' ?>>Tous</option>
                <option value="actifs" <?= $etat === 'actifs' ? 'selected' : '' ?>>Actifs</option>
                <option value="inactifs" <?= $etat === 'inactifs' ? 'selected' : '' ?>>Inactifs</option>
                <option value="ruptures" <?= $etat === 'ruptures' ? 'selected' : '' ?>>En rupture</option>
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button class="btn btn-success" type="submit">Filtrer</button>
            <a class="btn btn-outline-secondary" href="<?= e(ADMIN_URL) ?>/produits.php">Tout</a>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th scope="col">Produit</th>
                <th scope="col">Catégorie</th>
                <th scope="col">Prix</th>
                <th scope="col">Stock</th>
                <th scope="col">État</th>
                <th scope="col"><span class="visually-hidden">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produits as $produit): ?>
                <?php $image = imageProduit($produit); ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($image !== null): ?>
                                <img class="admin-thumb" src="<?= e($image) ?>" alt="">
                            <?php endif; ?>
                            <a href="<?= e(urlProduit((int) $produit['id'])) ?>"><?= e($produit['nom']) ?></a>
                        </div>
                    </td>
                    <td><?= e($produit['categorie_nom']) ?></td>
                    <td><?= e(formatPrix($produit['prix'])) ?></td>
                    <td>
                        <form class="d-flex gap-2" method="post" action="<?= e(ADMIN_URL) ?>/produits.php">
                            <?= champCsrf() ?>
                            <input type="hidden" name="action" value="stock">
                            <input type="hidden" name="id" value="<?= e((string) $produit['id']) ?>">
                            <input class="form-control form-control-sm cart-qty" type="number" name="stock" min="0"
                                   value="<?= e((string) $produit['stock']) ?>"
                                   aria-label="Stock de <?= e($produit['nom']) ?>">
                            <button class="btn btn-outline-secondary btn-sm" type="submit">OK</button>
                        </form>
                    </td>
                    <td>
                        <?php if ((int) $produit['actif'] === 1): ?>
                            <span class="badge bg-success">Actif</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <a class="btn btn-outline-secondary btn-sm"
                               href="<?= e(ADMIN_URL) ?>/produit-form.php?id=<?= e((string) $produit['id']) ?>">
                                Modifier
                            </a>
                            <form method="post" action="<?= e(ADMIN_URL) ?>/produits.php">
                                <?= champCsrf() ?>
                                <input type="hidden" name="action" value="basculer_actif">
                                <input type="hidden" name="id" value="<?= e((string) $produit['id']) ?>">
                                <button class="btn btn-outline-primary btn-sm" type="submit">
                                    <?= (int) $produit['actif'] === 1 ? 'Désactiver' : 'Activer' ?>
                                </button>
                            </form>
                            <form method="post" action="<?= e(ADMIN_URL) ?>/produits.php"
                                  onsubmit="return confirm('Supprimer définitivement ce produit ?');">
                                <?= champCsrf() ?>
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="id" value="<?= e((string) $produit['id']) ?>">
                                <button class="btn btn-outline-danger btn-sm" type="submit">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($produits === []): ?>
    <div class="alert alert-secondary">Aucun produit pour ce filtre.</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
