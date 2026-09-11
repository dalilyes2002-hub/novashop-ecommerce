<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/catalogue.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

const ADMIN_URL = BASE_URL . '/admin';

const UPLOAD_DIR = __DIR__ . '/../public/img';
const UPLOAD_MAX_OCTETS = 2 * 1024 * 1024;
const UPLOAD_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

function enregistrerImageProduit(array $fichier): array
{
    if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, ''];
    }

    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        return [null, 'L’envoi de l’image a échoué.'];
    }

    if (($fichier['size'] ?? 0) > UPLOAD_MAX_OCTETS) {
        return [null, 'Image trop lourde (2 Mo maximum).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $type = (string) $finfo->file($fichier['tmp_name']);

    if (!isset(UPLOAD_TYPES[$type])) {
        return [null, 'Format refusé. Utilise JPG, PNG ou WebP.'];
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        return [null, 'Dossier d’images introuvable.'];
    }

    $nomFichier = 'produit-' . bin2hex(random_bytes(8)) . '.' . UPLOAD_TYPES[$type];

    if (!move_uploaded_file($fichier['tmp_name'], UPLOAD_DIR . '/' . $nomFichier)) {
        return [null, 'Impossible d’enregistrer l’image.'];
    }

    return [$nomFichier, ''];
}

function supprimerImageProduit(?string $nomFichier): void
{
    if ($nomFichier === null || $nomFichier === '') {
        return;
    }

    $chemin = UPLOAD_DIR . '/' . basename($nomFichier);
    if (is_file($chemin)) {
        unlink($chemin);
    }
}

function statutsCommande(): array
{
    return ['en_attente', 'payee', 'expediee', 'livree', 'annulee'];
}

function changerStatutCommande(PDO $pdo, int $orderId, string $statut): string
{
    if (!in_array($statut, statutsCommande(), true)) {
        return 'Statut inconnu.';
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT statut FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $ancienStatut = $stmt->fetchColumn();

        if ($ancienStatut === false) {
            throw new RuntimeException('Commande introuvable.');
        }

        if ($ancienStatut !== $statut) {
            if ($statut === 'annulee') {
                rendreStockCommande($pdo, $orderId);
            } elseif ($ancienStatut === 'annulee') {
                reprendreStockCommande($pdo, $orderId);
            }
        }

        $stmt = $pdo->prepare('UPDATE orders SET statut = ? WHERE id = ?');
        $stmt->execute([$statut, $orderId]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'Le statut n’a pas pu être modifié.';
    }

    return '';
}

function rendreStockCommande(PDO $pdo, int $orderId): void
{
    $stmt = $pdo->prepare(
        'UPDATE products p
         INNER JOIN order_items oi ON oi.product_id = p.id
         SET p.stock = p.stock + oi.quantite
         WHERE oi.order_id = ?'
    );
    $stmt->execute([$orderId]);
}

function reprendreStockCommande(PDO $pdo, int $orderId): void
{
    $stmt = $pdo->prepare(
        'SELECT oi.product_id, oi.quantite, p.nom
         FROM order_items oi
         INNER JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ?
         FOR UPDATE'
    );
    $stmt->execute([$orderId]);
    $lignes = $stmt->fetchAll();

    $maj = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

    foreach ($lignes as $ligne) {
        $maj->execute([$ligne['quantite'], $ligne['product_id'], $ligne['quantite']]);

        if ($maj->rowCount() !== 1) {
            throw new RuntimeException(
                'Stock insuffisant pour « ' . $ligne['nom'] . ' » : impossible de réactiver cette commande.'
            );
        }
    }
}
