<?php

declare(strict_types=1);

/**
 * Enregistre une image envoyée par l'admin dans uploads/.
 * Trois contrôles : type réel du fichier, taille, nom regénéré.
 *
 * @return array{0: ?string, 1: string} [nom du fichier stocké, message d'erreur]
 */
function saveProductImage(array $fichier): array
{
    if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, ''];
    }

    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        return [null, 'L’envoi de l’image a échoué.'];
    }

    if (($fichier['size'] ?? 0) > UPLOAD_MAX_BYTES) {
        return [null, 'Image trop lourde (2 Mo maximum).'];
    }

    // On lit le type réel du contenu, pas celui annoncé par le navigateur :
    // un script PHP renommé en .jpg est ainsi refusé.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $type = (string) $finfo->file($fichier['tmp_name']);

    if (!isset(UPLOAD_TYPES[$type])) {
        return [null, 'Format refusé. Utilise JPG, PNG ou WebP.'];
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        return [null, 'Dossier uploads/ introuvable.'];
    }

    // Nom généré : on ne réutilise jamais celui fourni par l'utilisateur.
    $nomFichier = 'produit-' . bin2hex(random_bytes(8)) . '.' . UPLOAD_TYPES[$type];

    if (!move_uploaded_file($fichier['tmp_name'], UPLOAD_DIR . '/' . $nomFichier)) {
        return [null, 'Impossible d’enregistrer l’image. Vérifie les droits du dossier uploads/.'];
    }

    return [$nomFichier, ''];
}

function deleteProductImage(?string $nomFichier): void
{
    if ($nomFichier === null || $nomFichier === '') {
        return;
    }

    // basename : on refuse tout chemin qui tenterait de sortir du dossier.
    $chemin = UPLOAD_DIR . '/' . basename($nomFichier);
    if (is_file($chemin)) {
        unlink($chemin);
    }
}
