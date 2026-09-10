<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/catalogue.php';
require_once __DIR__ . '/config/database.php';

// Toutes les actions passent en POST puis redirigent : pas de double ajout si on rafraîchit.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantite = (int) ($_POST['quantite'] ?? 1);
    $retour = (string) ($_POST['retour'] ?? '/panier.php');

    // On ne redirige que vers une page interne connue.
    $retoursAutorises = ['/panier.php', '/produits.php', '/index.php'];
    if (!in_array($retour, $retoursAutorises, true)) {
        $retour = '/panier.php';
    }

    switch ($action) {
        case 'ajouter':
            $message = panierAjouter($pdo, $productId, $quantite);
            setFlash($message === '' ? 'success' : 'danger', $message === '' ? 'Produit ajouté au panier.' : $message);
            break;

        case 'modifier':
            $message = panierModifier($pdo, $productId, $quantite);
            setFlash($message === '' ? 'success' : 'danger', $message === '' ? 'Panier mis à jour.' : $message);
            break;

        case 'supprimer':
            panierSupprimer($productId);
            setFlash('success', 'Produit retiré du panier.');
            break;

        case 'vider':
            panierVider();
            setFlash('success', 'Panier vidé.');
            break;
    }

    redirect($retour);
}

$lignes = panierLignes($pdo);
$total = panierTotal($lignes);
$commandable = panierEstCommandable($lignes);

$pageTitle = 'Panier';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-3">Mon panier</h1>

<?php if ($lignes === []): ?>
    <div class="alert alert-secondary">Ton panier est vide.</div>
    <a class="btn btn-success" href="<?= e(BASE_URL) ?>/produits.php">Voir le catalogue</a>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Produit</th>
                    <th scope="col">Prix</th>
                    <th scope="col">Quantité</th>
                    <th scope="col">Sous-total</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td>
                            <a href="<?= e(urlProduit($ligne['id'])) ?>"><?= e($ligne['nom']) ?></a>
                            <div class="small text-muted"><?= e($ligne['categorie_nom']) ?></div>
                            <?php if (!$ligne['stock_suffisant']): ?>
                                <div class="small text-danger">
                                    Stock insuffisant : <?= e((string) $ligne['stock']) ?> restant(s)
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= e(formatPrix($ligne['prix'])) ?></td>
                        <td>
                            <form class="d-flex gap-2" method="post" action="<?= e(BASE_URL) ?>/panier.php">
                                <input type="hidden" name="action" value="modifier">
                                <input type="hidden" name="product_id" value="<?= e((string) $ligne['id']) ?>">
                                <input class="form-control form-control-sm cart-qty" type="number" name="quantite"
                                       min="1" max="<?= e((string) min($ligne['stock'], PANIER_QUANTITE_MAX)) ?>"
                                       value="<?= e((string) $ligne['quantite']) ?>"
                                       aria-label="Quantité pour <?= e($ligne['nom']) ?>">
                                <button class="btn btn-outline-secondary btn-sm" type="submit">OK</button>
                            </form>
                        </td>
                        <td><?= e(formatPrix($ligne['sous_total'])) ?></td>
                        <td class="text-end">
                            <form method="post" action="<?= e(BASE_URL) ?>/panier.php">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="product_id" value="<?= e((string) $ligne['id']) ?>">
                                <button class="btn btn-outline-danger btn-sm" type="submit">Retirer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th colspan="2"><?= e(formatPrix($total)) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <form method="post" action="<?= e(BASE_URL) ?>/panier.php">
            <input type="hidden" name="action" value="vider">
            <button class="btn btn-outline-danger" type="submit">Vider le panier</button>
        </form>
        <a class="btn btn-outline-secondary" href="<?= e(BASE_URL) ?>/produits.php">Continuer mes achats</a>

        <?php if (!$commandable): ?>
            <button class="btn btn-success" type="button" disabled>Commander</button>
        <?php elseif (isLoggedIn()): ?>
            <a class="btn btn-success" href="<?= e(BASE_URL) ?>/checkout.php">Commander</a>
        <?php else: ?>
            <a class="btn btn-success" href="<?= e(BASE_URL) ?>/login.php?retour=checkout">Se connecter pour commander</a>
        <?php endif; ?>
    </div>

    <?php if (!$commandable): ?>
        <p class="text-danger mt-2 mb-0">Ajuste les quantités : un produit dépasse le stock disponible.</p>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
