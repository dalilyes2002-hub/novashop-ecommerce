<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$erreurs = [];
$edition = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($action === 'creer' || $action === 'modifier') {
        if (mb_strlen($nom) < 2) {
            $erreurs[] = 'Le nom de la catégorie doit contenir au moins 2 caractères.';
        }
        if (mb_strlen($description) > 255) {
            $erreurs[] = 'La description est trop longue (255 caractères maximum).';
        }
    }

    if ($erreurs === []) {
        if ($action === 'creer') {
            $stmt = $pdo->prepare('INSERT INTO categories (nom, description) VALUES (?, ?)');
            $stmt->execute([$nom, $description]);
            setFlash('success', 'Catégorie créée.');
            redirect('/admin/categories.php');
        }

        if ($action === 'modifier') {
            $stmt = $pdo->prepare('UPDATE categories SET nom = ?, description = ? WHERE id = ?');
            $stmt->execute([$nom, $description, $id]);
            setFlash('success', 'Catégorie modifiée.');
            redirect('/admin/categories.php');
        }

        if ($action === 'supprimer') {
            // La clé étrangère est en RESTRICT, mais on vérifie avant pour donner un vrai message.
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $stmt->execute([$id]);
            $nbProduits = (int) $stmt->fetchColumn();

            if ($nbProduits > 0) {
                setFlash('danger', 'Suppression refusée : ' . $nbProduits . ' produit(s) sont encore dans cette catégorie.');
            } else {
                $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
                $stmt->execute([$id]);
                setFlash('success', 'Catégorie supprimée.');
            }
            redirect('/admin/categories.php');
        }
    } else {
        // On garde les valeurs saisies pour réafficher le formulaire.
        $edition = ['id' => $id, 'nom' => $nom, 'description' => $description, 'action' => $action];
    }
}

if ($edition === null && isset($_GET['modifier'])) {
    $stmt = $pdo->prepare('SELECT id, nom, description FROM categories WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['modifier']]);
    $trouvee = $stmt->fetch();
    if ($trouvee) {
        $edition = $trouvee + ['action' => 'modifier'];
    }
}

$stmt = $pdo->query(
    'SELECT c.id, c.nom, c.description, COUNT(p.id) AS nb_produits
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.nom, c.description
     ORDER BY c.nom'
);
$categories = $stmt->fetchAll();

$pageTitle = 'Catégories (admin)';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<h1 class="h3 mb-3">Catégories</h1>

<?php if ($erreurs): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($erreurs as $erreur): ?>
                <li><?= e($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="info-card">
            <h2 class="h5 mb-3">
                <?= $edition !== null && $edition['action'] === 'modifier' ? 'Modifier la catégorie' : 'Nouvelle catégorie' ?>
            </h2>
            <form method="post" action="<?= e(ADMIN_URL) ?>/categories.php">
                <input type="hidden" name="action" value="<?= e($edition !== null && $edition['action'] === 'modifier' ? 'modifier' : 'creer') ?>">
                <?php if ($edition !== null && $edition['action'] === 'modifier'): ?>
                    <input type="hidden" name="id" value="<?= e((string) $edition['id']) ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label" for="nom">Nom</label>
                    <input class="form-control" type="text" id="nom" name="nom" required
                           value="<?= e((string) ($edition['nom'] ?? '')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <input class="form-control" type="text" id="description" name="description" maxlength="255"
                           value="<?= e((string) ($edition['description'] ?? '')) ?>">
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" type="submit">Enregistrer</button>
                    <?php if ($edition !== null): ?>
                        <a class="btn btn-outline-secondary" href="<?= e(ADMIN_URL) ?>/categories.php">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col">Produits</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $categorie): ?>
                        <tr>
                            <td>
                                <?= e($categorie['nom']) ?>
                                <div class="small text-muted"><?= e((string) $categorie['description']) ?></div>
                            </td>
                            <td><?= e((string) $categorie['nb_produits']) ?></td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a class="btn btn-outline-secondary btn-sm"
                                       href="<?= e(ADMIN_URL) ?>/categories.php?modifier=<?= e((string) $categorie['id']) ?>">
                                        Modifier
                                    </a>
                                    <form method="post" action="<?= e(ADMIN_URL) ?>/categories.php"
                                          onsubmit="return confirm('Supprimer cette catégorie ?');">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id" value="<?= e((string) $categorie['id']) ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit"
                                                <?= (int) $categorie['nb_produits'] > 0 ? 'disabled title="Catégorie non vide"' : '' ?>>
                                            Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
