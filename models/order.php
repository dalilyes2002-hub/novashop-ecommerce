<?php

declare(strict_types=1);

// Accès aux tables orders et order_items.

/**
 * Enregistre la commande dans une transaction : relecture des stocks,
 * insertion des lignes avec le prix du moment, puis décrément du stock.
 * En cas de problème, rien n'est écrit.
 *
 * @param list<array{id: int, nom: string, quantite: int}> $lignes
 * @return int identifiant de la commande créée
 * @throws RuntimeException si un produit n'est plus disponible
 */
function createOrder(PDO $pdo, int $userId, array $lignes, string $adresse, string $modePaiement): int
{
    $statut = $modePaiement === 'carte' ? 'payee' : 'en_attente';

    try {
        $pdo->beginTransaction();

        // FOR UPDATE : on verrouille les lignes le temps de la commande.
        $ids = array_column($lignes, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare(
            'SELECT id, prix, stock FROM products
             WHERE id IN (' . $placeholders . ') AND actif = 1
             FOR UPDATE'
        );
        $stmt->execute($ids);

        $verrouilles = [];
        foreach ($stmt->fetchAll() as $produit) {
            $verrouilles[(int) $produit['id']] = $produit;
        }

        $total = 0.0;
        foreach ($lignes as $ligne) {
            $produit = $verrouilles[$ligne['id']] ?? null;
            if ($produit === null) {
                throw new RuntimeException('Le produit « ' . $ligne['nom'] . ' » n’est plus disponible.');
            }
            if ((int) $produit['stock'] < $ligne['quantite']) {
                throw new RuntimeException('Stock insuffisant pour « ' . $ligne['nom'] . ' ».');
            }
            // Prix de référence : celui de la base, pas celui de la session.
            $total += (float) $produit['prix'] * $ligne['quantite'];
        }
        $total = round($total, 2);

        $stmt = $pdo->prepare(
            'INSERT INTO orders (user_id, total, adresse_livraison, mode_paiement, statut)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $total, $adresse, $modePaiement, $statut]);
        $orderId = (int) $pdo->lastInsertId();

        $stmtLigne = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, quantite, prix_unitaire)
             VALUES (?, ?, ?, ?)'
        );
        $stmtStock = $pdo->prepare(
            'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?'
        );

        foreach ($lignes as $ligne) {
            $prixUnitaire = (float) $verrouilles[$ligne['id']]['prix'];
            $stmtLigne->execute([$orderId, $ligne['id'], $ligne['quantite'], $prixUnitaire]);
            $stmtStock->execute([$ligne['quantite'], $ligne['id'], $ligne['quantite']]);

            if ($stmtStock->rowCount() !== 1) {
                throw new RuntimeException('Le stock de « ' . $ligne['nom'] . ' » a changé.');
            }
        }

        $pdo->commit();

        return $orderId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/**
 * Commandes d'un client donné.
 */
function getOrdersByUser(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT o.id, o.total, o.statut, o.mode_paiement, o.created_at, COUNT(oi.id) AS nb_lignes
         FROM orders o
         LEFT JOIN order_items oi ON oi.order_id = o.id
         WHERE o.user_id = ?
         GROUP BY o.id, o.total, o.statut, o.mode_paiement, o.created_at
         ORDER BY o.created_at DESC, o.id DESC'
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

/**
 * Commande d'un client : le filtre user_id empêche de lire celle d'un autre.
 */
function findOrderForUser(PDO $pdo, int $orderId, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, total, adresse_livraison, mode_paiement, statut, created_at
         FROM orders
         WHERE id = ? AND user_id = ?
         LIMIT 1'
    );
    $stmt->execute([$orderId, $userId]);
    $commande = $stmt->fetch();

    return $commande ?: null;
}

function getOrderItems(PDO $pdo, int $orderId): array
{
    $stmt = $pdo->prepare(
        'SELECT oi.quantite, oi.prix_unitaire, oi.product_id, p.nom
         FROM order_items oi
         INNER JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ?
         ORDER BY p.nom'
    );
    $stmt->execute([$orderId]);

    return $stmt->fetchAll();
}

// --- Côté administration ---

function getAllOrders(PDO $pdo, string $filtreStatut = ''): array
{
    $sql = 'SELECT o.id, o.total, o.statut, o.mode_paiement, o.created_at,
                   u.id AS client_id, u.prenom, u.nom,
                   COUNT(oi.id) AS nb_lignes
            FROM orders o
            INNER JOIN users u ON u.id = o.user_id
            LEFT JOIN order_items oi ON oi.order_id = o.id';
    $params = [];

    if ($filtreStatut !== '') {
        $sql .= ' WHERE o.statut = ?';
        $params[] = $filtreStatut;
    }

    $sql .= ' GROUP BY o.id, o.total, o.statut, o.mode_paiement, o.created_at, u.id, u.prenom, u.nom
              ORDER BY o.created_at DESC, o.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * L'admin voit toutes les commandes, sans filtre user_id.
 */
function findOrderForAdmin(PDO $pdo, int $orderId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT o.id, o.total, o.adresse_livraison, o.mode_paiement, o.statut, o.created_at,
                u.id AS client_id, u.prenom, u.nom, u.email, u.telephone
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         WHERE o.id = ?
         LIMIT 1'
    );
    $stmt->execute([$orderId]);
    $commande = $stmt->fetch();

    return $commande ?: null;
}

function updateOrderStatus(PDO $pdo, int $orderId, string $statut): void
{
    $stmt = $pdo->prepare('UPDATE orders SET statut = ? WHERE id = ?');
    $stmt->execute([$statut, $orderId]);
}

function getRecentOrders(PDO $pdo, int $limit = 5): array
{
    $stmt = $pdo->prepare(
        'SELECT o.id, o.total, o.statut, o.created_at, u.prenom, u.nom
         FROM orders o
         INNER JOIN users u ON u.id = o.user_id
         ORDER BY o.created_at DESC, o.id DESC
         LIMIT ' . (int) $limit
    );
    $stmt->execute();

    return $stmt->fetchAll();
}

function countOrders(PDO $pdo, string $statut = ''): int
{
    if ($statut === '') {
        return (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE statut = ?');
    $stmt->execute([$statut]);

    return (int) $stmt->fetchColumn();
}

function totalRevenue(PDO $pdo): float
{
    return (float) $pdo->query(
        "SELECT COALESCE(SUM(total), 0) FROM orders WHERE statut != 'annulee'"
    )->fetchColumn();
}
