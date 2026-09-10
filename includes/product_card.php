<?php
/**
 * Attend $produit : id, nom, prix, stock, actif, image, categorie_nom.
 */
$image = imageProduit($produit);
$disponible = estDisponible($produit);
// Après l'ajout, on revient sur la page courante (panier.php par défaut).
$retourPanier = '/' . basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'panier.php'));
?>
<article class="product-card">
    <?php if ($image !== null): ?>
        <img class="product-thumb" src="<?= e($image) ?>" alt="<?= e($produit['nom']) ?>">
    <?php else: ?>
        <div class="product-thumb product-thumb-empty">Pas d’image</div>
    <?php endif; ?>

    <div class="product-body">
        <p class="product-category"><?= e($produit['categorie_nom']) ?></p>
        <h3 class="product-name"><?= e($produit['nom']) ?></h3>
        <p class="product-price"><?= e(formatPrix($produit['prix'])) ?></p>

        <?php if ($disponible): ?>
            <p class="product-stock text-success">En stock (<?= e((string) $produit['stock']) ?>)</p>
        <?php else: ?>
            <p class="product-stock text-danger">Indisponible</p>
        <?php endif; ?>

        <div class="product-actions">
            <a class="btn btn-outline-success btn-sm" href="<?= e(urlProduit((int) $produit['id'])) ?>">Voir</a>
            <?php if ($disponible): ?>
                <form method="post" action="<?= e(BASE_URL) ?>/panier.php">
                    <input type="hidden" name="action" value="ajouter">
                    <input type="hidden" name="product_id" value="<?= e((string) $produit['id']) ?>">
                    <input type="hidden" name="quantite" value="1">
                    <input type="hidden" name="retour" value="<?= e($retourPanier) ?>">
                    <button class="btn btn-success btn-sm" type="submit">Ajouter au panier</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</article>
