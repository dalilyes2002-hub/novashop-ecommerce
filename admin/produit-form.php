<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$erreurs = [];

$produit = [
    'id' => 0,
    'category_id' => 0,
    'nom' => '',
    'description' => '',
    'prix' => '',
    'stock' => '0',
    'image' => null,
    'actif' => 1,
];

if ($id > 0) {
    $stmt = $pdo->prepare(
        'SELECT id, category_id, nom, description, prix, stock, image, actif
         FROM products WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $trouve = $stmt->fetch();

    if (!$trouve) {
        setFlash('danger', 'Produit introuvable.');
        redirect('/admin/produits.php');
    }
    $produit = $trouve;
}

$stmt = $pdo->query('SELECT id, nom FROM categories ORDER BY nom');
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produit['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $produit['nom'] = trim((string) ($_POST['nom'] ?? ''));
    $produit['description'] = trim((string) ($_POST['description'] ?? ''));
    $prixBrut = str_replace(',', '.', trim((string) ($_POST['prix'] ?? '')));
    $produit['prix'] = $prixBrut;
    $produit['stock'] = (string) (int) ($_POST['stock'] ?? 0);
    $produit['actif'] = isset($_POST['actif']) ? 1 : 0;

    if (mb_strlen($produit['nom']) < 2) {
        $erreurs[] = 'Le nom du produit doit contenir au moins 2 caractères.';
    }

    $categorieValide = false;
    foreach ($categories as $categorie) {
        if ((int) $categorie['id'] === $produit['category_id']) {
            $categorieValide = true;
            break;
        }
    }
    if (!$categorieValide) {
        $erreurs[] = 'Choisis une catégorie existante.';
    }

    if (!is_numeric($prixBrut) || (float) $prixBrut < 0) {
        $erreurs[] = 'Le prix doit être un nombre positif.';
    }

    if ((int) $produit['stock'] < 0) {
        $erreurs[] = 'Le stock ne peut pas être négatif.';
    }

    $nouvelleImage = null;
    if (isset($_FILES['image'])) {
        [$nouvelleImage, $erreurImage] = enregistrerImageProduit($_FILES['image']);
        if ($erreurImage !== '') {
            $erreurs[] = $erreurImage;
        }
    }

    if ($erreurs === []) {
        $imageFinale = $nouvelleImage ?? $produit['image'];

        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET category_id = ?, nom = ?, description = ?, prix = ?, stock = ?, image = ?, actif = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $produit['category_id'],
                $produit['nom'],
                $produit['description'],
                (float) $prixBrut,
                (int) $produit['stock'],
                $imageFinale,
                $produit['actif'],
                $id,
            ]);

            // L'ancien fichier ne sert plus une fois remplacé.
            if ($nouvelleImage !== null && $produit['image'] !== null && $produit['image'] !== $nouvelleImage) {
                supprimerImageProduit((string) $produit['image']);
            }

            setFlash('success', 'Produit modifié.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO products (category_id, nom, description, prix, stock, image, actif)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $produit['category_id'],
                $produit['nom'],
                $produit['description'],
                (float) $prixBrut,
                (int) $produit['stock'],
                $imageFinale,
                $produit['actif'],
            ]);
            setFlash('success', 'Produit créé.');
        }

        redirect('/admin/produits.php');
    }

    // En cas d'erreur, on garde l'image déjà envoyée pour ne pas la redemander.
    if ($nouvelleImage !== null) {
        $produit['image'] = $nouvelleImage;
    }
}

$imageActuelle = imageProduit($produit);

$pageTitle = $id > 0 ? 'Modifier un produit' : 'Nouveau produit';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<h1 class="h3 mb-3"><?= e($pageTitle) ?></h1>

<?php if ($erreurs): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($erreurs as $erreur): ?>
                <li><?= e($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="auth-card auth-card-wide">
    <form method="post" action="<?= e(ADMIN_URL) ?>/produit-form.php<?= $id > 0 ? '?id=' . e((string) $id) : '' ?>"
          enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-12 col-md-8">
                <label class="form-label" for="nom">Nom</label>
                <input class="form-control" type="text" id="nom" name="nom" required
                       value="<?= e((string) $produit['nom']) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="category_id">Catégorie</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="0">Choisir…</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?= e((string) $categorie['id']) ?>"
                            <?= (int) $produit['category_id'] === (int) $categorie['id'] ? 'selected' : '' ?>>
                            <?= e($categorie['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?= e((string) $produit['description']) ?></textarea>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="prix">Prix (€)</label>
                <input class="form-control" type="text" id="prix" name="prix" required
                       value="<?= e((string) $produit['prix']) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="stock">Stock</label>
                <input class="form-control" type="number" id="stock" name="stock" min="0"
                       value="<?= e((string) $produit['stock']) ?>">
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1"
                        <?= (int) $produit['actif'] === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="actif">Produit visible dans la boutique</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="image">Image (JPG, PNG ou WebP — 2 Mo maximum)</label>
                <input class="form-control" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                <?php if ($imageActuelle !== null): ?>
                    <div class="mt-2 d-flex align-items-center gap-2">
                        <img class="admin-thumb" src="<?= e($imageActuelle) ?>" alt="">
                        <span class="small text-muted">Image actuelle — envoie un fichier pour la remplacer.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button class="btn btn-success" type="submit">Enregistrer</button>
            <a class="btn btn-outline-secondary" href="<?= e(ADMIN_URL) ?>/produits.php">Annuler</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
