<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/catalogue.php';
require_once __DIR__ . '/../config/database.php';

// Barrière unique : toute page qui inclut ce fichier est réservée aux admins.
requireAdmin();

const ADMIN_URL = BASE_URL . '/admin';

const UPLOAD_DIR = __DIR__ . '/../public/img';
const UPLOAD_MAX_OCTETS = 2 * 1024 * 1024;
const UPLOAD_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

/**
 * Valide et déplace une image envoyée par l'admin.
 *
 * @return array{0: ?string, 1: string} [nom du fichier stocké, message d'erreur]
 */
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

    // On lit le type réel du fichier, pas celui annoncé par le navigateur.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $type = (string) $finfo->file($fichier['tmp_name']);

    if (!isset(UPLOAD_TYPES[$type])) {
        return [null, 'Format refusé. Utilise JPG, PNG ou WebP.'];
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        return [null, 'Dossier d’images introuvable.'];
    }

    // Nom généré : on ne réutilise jamais le nom fourni par l'utilisateur.
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

    // basename : on refuse tout chemin qui tenterait de sortir du dossier d'images.
    $chemin = UPLOAD_DIR . '/' . basename($nomFichier);
    if (is_file($chemin)) {
        unlink($chemin);
    }
}

/**
 * @return list<string>
 */
function statutsCommande(): array
{
    return ['en_attente', 'payee', 'expediee', 'livree', 'annulee'];
}
