<?php

declare(strict_types=1);

// Accès à la table categories.

function getAllCategories(PDO $pdo): array
{
    return $pdo->query('SELECT id, nom, description FROM categories ORDER BY nom')->fetchAll();
}

function findCategoryById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, nom, description FROM categories WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $categorie = $stmt->fetch();

    return $categorie ?: null;
}

/**
 * Catégories avec le nombre de produits visibles en boutique.
 */
function getCategoriesWithCounts(PDO $pdo): array
{
    return $pdo->query(
        'SELECT c.id, c.nom, c.description,
                COUNT(p.id) AS nb_produits,
                COALESCE(SUM(CASE WHEN p.stock > 0 THEN 1 ELSE 0 END), 0) AS nb_en_stock
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id AND p.actif = 1
         GROUP BY c.id, c.nom, c.description
         ORDER BY c.nom'
    )->fetchAll();
}

/**
 * Pour l'admin : compte tous les produits, actifs ou non.
 */
function getCategoriesForAdmin(PDO $pdo): array
{
    return $pdo->query(
        'SELECT c.id, c.nom, c.description, COUNT(p.id) AS nb_produits
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id
         GROUP BY c.id, c.nom, c.description
         ORDER BY c.nom'
    )->fetchAll();
}

function createCategory(PDO $pdo, string $nom, string $description): void
{
    $stmt = $pdo->prepare('INSERT INTO categories (nom, description) VALUES (?, ?)');
    $stmt->execute([$nom, $description]);
}

function updateCategory(PDO $pdo, int $id, string $nom, string $description): void
{
    $stmt = $pdo->prepare('UPDATE categories SET nom = ?, description = ? WHERE id = ?');
    $stmt->execute([$nom, $description, $id]);
}

function deleteCategory(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
}

function countProductsInCategory(PDO $pdo, int $categoryId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $stmt->execute([$categoryId]);

    return (int) $stmt->fetchColumn();
}

function countCategories(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
}
