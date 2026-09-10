<?php

declare(strict_types=1);

// Accès à la table users. Requêtes préparées uniquement.

function findUserByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, prenom, nom, email, telephone, adresse, mot_de_passe, role
         FROM users WHERE email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function findUserById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, prenom, nom, email, telephone, adresse, role, created_at
         FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function emailExists(PDO $pdo, string $email, ?int $exceptId = null): bool
{
    if ($exceptId === null) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $stmt->execute([$email, $exceptId]);
    }

    return (bool) $stmt->fetch();
}

function createUser(PDO $pdo, array $data, string $passwordHash, string $role = 'client'): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO users (prenom, nom, email, telephone, adresse, mot_de_passe, role)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['prenom'],
        $data['nom'],
        $data['email'],
        $data['telephone'],
        $data['adresse'],
        $passwordHash,
        $role,
    ]);

    return (int) $pdo->lastInsertId();
}

function updateUser(PDO $pdo, int $id, array $data): void
{
    $stmt = $pdo->prepare(
        'UPDATE users SET prenom = ?, nom = ?, email = ?, telephone = ?, adresse = ? WHERE id = ?'
    );
    $stmt->execute([
        $data['prenom'],
        $data['nom'],
        $data['email'],
        $data['telephone'],
        $data['adresse'],
        $id,
    ]);
}

function updateUserPassword(PDO $pdo, int $id, string $passwordHash): void
{
    $stmt = $pdo->prepare('UPDATE users SET mot_de_passe = ? WHERE id = ?');
    $stmt->execute([$passwordHash, $id]);
}

function getUserAddress(PDO $pdo, int $id): string
{
    $stmt = $pdo->prepare('SELECT adresse FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);

    return (string) $stmt->fetchColumn();
}

/**
 * Liste des clients avec leur nombre de commandes et leur total d'achats.
 */
function getClients(PDO $pdo, string $search = ''): array
{
    $sql = "SELECT u.id, u.prenom, u.nom, u.email, u.telephone, u.created_at,
                   COUNT(o.id) AS nb_commandes,
                   COALESCE(SUM(CASE WHEN o.statut != 'annulee' THEN o.total ELSE 0 END), 0) AS total_achats
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id
            WHERE u.role = 'client'";
    $params = [];

    if ($search !== '') {
        $sql .= ' AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $sql .= ' GROUP BY u.id, u.prenom, u.nom, u.email, u.telephone, u.created_at
              ORDER BY u.nom, u.prenom';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function countClients(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
}
