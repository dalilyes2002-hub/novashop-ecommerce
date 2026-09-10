<?php

declare(strict_types=1);

/**
 * Panier en session : tableau product_id => quantite.
 * Ce fichier décide, les requêtes sont dans models/product.php.
 */

function cartItems(): array
{
    return $_SESSION['panier'] ?? [];
}

function cartSave(array $panier): void
{
    $_SESSION['panier'] = $panier;
}

function cartClear(): void
{
    unset($_SESSION['panier']);
}

function cartCount(): int
{
    return array_sum(cartItems());
}

/**
 * @return string '' si tout va bien, sinon le message à afficher
 */
function cartAdd(PDO $pdo, int $productId, int $quantite): string
{
    if ($quantite < 1) {
        return 'Quantité invalide.';
    }

    $stock = getSellableStock($pdo, $productId);
    if ($stock === null) {
        return 'Ce produit n’est pas disponible.';
    }
    if ($stock === 0) {
        return 'Ce produit est en rupture de stock.';
    }

    $panier = cartItems();
    $plafond = min($stock, CART_MAX_QUANTITY);
    $nouvelle = ($panier[$productId] ?? 0) + $quantite;

    if ($nouvelle > $plafond) {
        $panier[$productId] = $plafond;
        cartSave($panier);

        return 'Quantité limitée à ' . $plafond . ' pour ce produit.';
    }

    $panier[$productId] = $nouvelle;
    cartSave($panier);

    return '';
}

function cartUpdate(PDO $pdo, int $productId, int $quantite): string
{
    $panier = cartItems();
    if (!isset($panier[$productId])) {
        return 'Ce produit n’est pas dans le panier.';
    }

    if ($quantite < 1) {
        cartRemove($productId);

        return '';
    }

    $stock = getSellableStock($pdo, $productId);
    if ($stock === null || $stock === 0) {
        cartRemove($productId);

        return 'Ce produit n’est plus disponible, il a été retiré du panier.';
    }

    $plafond = min($stock, CART_MAX_QUANTITY);
    if ($quantite > $plafond) {
        $panier[$productId] = $plafond;
        cartSave($panier);

        return 'Quantité limitée à ' . $plafond . ' pour ce produit.';
    }

    $panier[$productId] = $quantite;
    cartSave($panier);

    return '';
}

function cartRemove(int $productId): void
{
    $panier = cartItems();
    unset($panier[$productId]);
    cartSave($panier);
}

/**
 * Lignes du panier avec sous-totaux. Les produits retirés du catalogue
 * sortent automatiquement de la session.
 */
function cartLines(PDO $pdo): array
{
    $panier = cartItems();
    if ($panier === []) {
        return [];
    }

    $ids = array_keys($panier);
    $produits = getProductsByIds($pdo, $ids);

    $lignes = [];
    $trouves = [];

    foreach ($produits as $produit) {
        $id = (int) $produit['id'];
        $trouves[] = $id;
        $quantite = (int) $panier[$id];

        $lignes[] = [
            'id' => $id,
            'nom' => $produit['nom'],
            'categorie_nom' => $produit['categorie_nom'],
            'image' => $produit['image'],
            'prix' => (float) $produit['prix'],
            'stock' => (int) $produit['stock'],
            'quantite' => $quantite,
            'sous_total' => round((float) $produit['prix'] * $quantite, 2),
            'stock_suffisant' => $quantite <= (int) $produit['stock'],
        ];
    }

    $disparus = array_diff($ids, $trouves);
    if ($disparus !== []) {
        foreach ($disparus as $idDisparu) {
            unset($panier[$idDisparu]);
        }
        cartSave($panier);
    }

    return $lignes;
}

function cartTotal(array $lignes): float
{
    $total = 0.0;
    foreach ($lignes as $ligne) {
        $total += $ligne['sous_total'];
    }

    return round($total, 2);
}

function cartIsOrderable(array $lignes): bool
{
    if ($lignes === []) {
        return false;
    }

    foreach ($lignes as $ligne) {
        if (!$ligne['stock_suffisant']) {
            return false;
        }
    }

    return true;
}
