<?php

declare(strict_types=1);

// Accès à la table products.

/**
 * Produits visibles en boutique, avec recherche et filtres.
 *
 * @param array{q?: string, category_id?: int, prix_min?: ?float, prix_max?: ?float, en_stock?: bool, tri?: string} $filtres
 */
function searchProducts(PDO $pdo, array $filtres = [], ?int $limit = null): array
{
    // Le tri vient de l'URL : liste blanche obligatoire, jamais de concaténation brute.
    $tris = [
        'nom' => 'p.nom ASC',
        'prix_asc' => 'p.prix ASC',
        'prix_desc' => 'p.prix DESC',
        'recent' => 'p.id DESC',
    ];
    $tri = $tris[$filtres['tri'] ?? 'nom'] ?? $tris['nom'];

    $conditions = ['p.actif = 1'];
    $params = [];

    if (!empty($filtres['q'])) {
        $conditions[] = '(p.nom LIKE ? OR p.description LIKE ?)';
        $params[] = '%' . $filtres['q'] . '%';
        $params[] = '%' . $filtres['q'] . '%';
    }

    if (!empty($filtres['category_id'])) {
        $conditions[] = 'p.category_id = ?';
        $params[] = (int) $filtres['category_id'];
    }

    if (isset($filtres['prix_min']) && $filtres['prix_min'] !== null) {
        $conditions[] = 'p.prix >= ?';
        $params[] = (float) $filtres['prix_min'];
    }

    if (isset($filtres['prix_max']) && $filtres['prix_max'] !== null) {
        $conditions[] = 'p.prix <= ?';
        $params[] = (float) $filtres['prix_max'];
    }

    if (!empty($filtres['en_stock'])) {
        $conditions[] = 'p.stock > 0';
    }

    $sql = 'SELECT p.id, p.nom, p.prix, p.stock, p.actif, p.image, c.nom AS categorie_nom
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY ' . $tri;

    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int) $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Fiche d'un produit visible en boutique.
 */
function findVisibleProduct(PDO $pdo, int $id): ?array
{
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

    return $produit ?: null;
}

function getRelatedProducts(PDO $pdo, int $categoryId, int $exceptId, int $limit = 3): array
{
    $stmt = $pdo->prepare(
        'SELECT p.id, p.nom, p.prix, p.stock, p.actif, p.image, c.nom AS categorie_nom
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         WHERE p.actif = 1 AND p.category_id = ? AND p.id != ?
         ORDER BY p.nom
         LIMIT ' . (int) $limit
    );
    $stmt->execute([$categoryId, $exceptId]);

    return $stmt->fetchAll();
}

// --- Côté administration ---

function findProductById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, category_id, nom, description, prix, stock, image, actif
         FROM products WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $produit = $stmt->fetch();

    return $produit ?: null;
}

/**
 * Liste admin : tous les produits, avec filtres catégorie et état.
 */
function getProductsForAdmin(PDO $pdo, int $categoryId = 0, string $etat = 'tous'): array
{
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

    return $stmt->fetchAll();
}

function createProduct(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO products (category_id, nom, description, prix, stock, image, actif)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        (int) $data['category_id'],
        $data['nom'],
        $data['description'],
        (float) $data['prix'],
        (int) $data['stock'],
        $data['image'],
        (int) $data['actif'],
    ]);

    return (int) $pdo->lastInsertId();
}

function updateProduct(PDO $pdo, int $id, array $data): void
{
    $stmt = $pdo->prepare(
        'UPDATE products
         SET category_id = ?, nom = ?, description = ?, prix = ?, stock = ?, image = ?, actif = ?
         WHERE id = ?'
    );
    $stmt->execute([
        (int) $data['category_id'],
        $data['nom'],
        $data['description'],
        (float) $data['prix'],
        (int) $data['stock'],
        $data['image'],
        (int) $data['actif'],
        $id,
    ]);
}

function deleteProduct(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
}

function toggleProductActive(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('UPDATE products SET actif = 1 - actif WHERE id = ?');
    $stmt->execute([$id]);
}

function updateProductStock(PDO $pdo, int $id, int $stock): void
{
    $stmt = $pdo->prepare('UPDATE products SET stock = ? WHERE id = ?');
    $stmt->execute([$stock, $id]);
}

function countProductOrderLines(PDO $pdo, int $productId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
    $stmt->execute([$productId]);

    return (int) $stmt->fetchColumn();
}

/**
 * Stock d'un produit vendable, ou null si le produit n'est pas au catalogue.
 */
function getSellableStock(PDO $pdo, int $productId): ?int
{
    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ? AND actif = 1 LIMIT 1');
    $stmt->execute([$productId]);
    $stock = $stmt->fetchColumn();

    return $stock === false ? null : (int) $stock;
}

/**
 * Produits du panier, pour recalculer les lignes.
 *
 * @param list<int> $ids
 */
function getProductsByIds(PDO $pdo, array $ids): array
{
    if ($ids === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        'SELECT p.id, p.nom, p.prix, p.stock, p.image, c.nom AS categorie_nom
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         WHERE p.id IN (' . $placeholders . ') AND p.actif = 1
         ORDER BY p.nom'
    );
    $stmt->execute($ids);

    return $stmt->fetchAll();
}

function countProducts(PDO $pdo, ?bool $actifs = null): int
{
    if ($actifs === null) {
        return (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE actif = ?');
    $stmt->execute([$actifs ? 1 : 0]);

    return (int) $stmt->fetchColumn();
}

function countOutOfStock(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock = 0')->fetchColumn();
}

function getLowStockProducts(PDO $pdo, int $seuil = 5, int $limit = 5): array
{
    $stmt = $pdo->prepare(
        'SELECT id, nom, stock, actif FROM products WHERE stock <= ? ORDER BY stock, nom LIMIT ' . (int) $limit
    );
    $stmt->execute([$seuil]);

    return $stmt->fetchAll();
}
