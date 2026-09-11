<?php

declare(strict_types=1);

const PANIER_QUANTITE_MAX = 20;

function panier(): array
{
    return $_SESSION['panier'] ?? [];
}

function panierEnregistrer(array $panier): void
{
    $_SESSION['panier'] = $panier;
}

function panierVider(): void
{
    unset($_SESSION['panier']);
}

function panierNbArticles(): int
{
    return array_sum(panier());
}

function stockDisponible(PDO $pdo, int $productId): ?int
{
    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ? AND actif = 1 LIMIT 1');
    $stmt->execute([$productId]);
    $stock = $stmt->fetchColumn();

    return $stock === false ? null : (int) $stock;
}

function panierAjouter(PDO $pdo, int $productId, int $quantite): string
{
    if ($quantite < 1) {
        return 'Quantité invalide.';
    }

    $stock = stockDisponible($pdo, $productId);
    if ($stock === null) {
        return 'Ce produit n’est pas disponible.';
    }
    if ($stock === 0) {
        return 'Ce produit est en rupture de stock.';
    }

    $panier = panier();
    $nouvelleQuantite = ($panier[$productId] ?? 0) + $quantite;
    $plafond = min($stock, PANIER_QUANTITE_MAX);

    if ($nouvelleQuantite > $plafond) {
        $nouvelleQuantite = $plafond;
        $panier[$productId] = $nouvelleQuantite;
        panierEnregistrer($panier);

        return 'Quantité limitée à ' . $plafond . ' pour ce produit.';
    }

    $panier[$productId] = $nouvelleQuantite;
    panierEnregistrer($panier);

    return '';
}

function panierModifier(PDO $pdo, int $productId, int $quantite): string
{
    $panier = panier();
    if (!isset($panier[$productId])) {
        return 'Ce produit n’est pas dans le panier.';
    }

    if ($quantite < 1) {
        panierSupprimer($productId);

        return '';
    }

    $stock = stockDisponible($pdo, $productId);
    if ($stock === null || $stock === 0) {
        panierSupprimer($productId);

        return 'Ce produit n’est plus disponible, il a été retiré du panier.';
    }

    $plafond = min($stock, PANIER_QUANTITE_MAX);
    if ($quantite > $plafond) {
        $panier[$productId] = $plafond;
        panierEnregistrer($panier);

        return 'Quantité limitée à ' . $plafond . ' pour ce produit.';
    }

    $panier[$productId] = $quantite;
    panierEnregistrer($panier);

    return '';
}

function panierSupprimer(int $productId): void
{
    $panier = panier();
    unset($panier[$productId]);
    panierEnregistrer($panier);
}

function panierLignes(PDO $pdo): array
{
    $panier = panier();
    if ($panier === []) {
        return [];
    }

    $ids = array_keys($panier);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        'SELECT p.id, p.nom, p.prix, p.stock, p.image, c.nom AS categorie_nom
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         WHERE p.id IN (' . $placeholders . ') AND p.actif = 1
         ORDER BY p.nom'
    );
    $stmt->execute($ids);
    $produits = $stmt->fetchAll();

    $lignes = [];
    $idsTrouves = [];

    foreach ($produits as $produit) {
        $id = (int) $produit['id'];
        $idsTrouves[] = $id;
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

    $disparus = array_diff($ids, $idsTrouves);
    if ($disparus !== []) {
        foreach ($disparus as $idDisparu) {
            unset($panier[$idDisparu]);
        }
        panierEnregistrer($panier);
    }

    return $lignes;
}

function panierTotal(array $lignes): float
{
    $total = 0.0;
    foreach ($lignes as $ligne) {
        $total += $ligne['sous_total'];
    }

    return round($total, 2);
}

function panierEstCommandable(array $lignes): bool
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
