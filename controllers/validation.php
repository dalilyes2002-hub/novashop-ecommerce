<?php

declare(strict_types=1);

/**
 * Validation du formulaire d'inscription et du profil.
 *
 * @param bool $motDePasseObligatoire true à l'inscription, false sur le profil
 * @return array{0: list<string>, 1: array<string, mixed>} [erreurs, données nettoyées]
 */
function validateAccountForm(PDO $pdo, array $data, bool $motDePasseObligatoire, ?int $userId = null): array
{
    $erreurs = [];

    $prenom = trim((string) ($data['prenom'] ?? ''));
    $nom = trim((string) ($data['nom'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $telephone = trim((string) ($data['telephone'] ?? ''));
    $adresse = trim((string) ($data['adresse'] ?? ''));
    $motDePasse = (string) ($data['mot_de_passe'] ?? '');
    $confirmation = (string) ($data['mot_de_passe_confirm'] ?? '');

    if (mb_strlen($prenom) < 2) {
        $erreurs[] = 'Le prénom doit contenir au moins 2 caractères.';
    }

    if (mb_strlen($nom) < 2) {
        $erreurs[] = 'Le nom doit contenir au moins 2 caractères.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'L’adresse email n’est pas valide.';
    } elseif (emailExists($pdo, $email, $userId)) {
        $erreurs[] = 'Cet email est déjà utilisé.';
    }

    $chiffres = preg_replace('/\D+/', '', $telephone) ?? '';
    if (strlen($chiffres) < 10) {
        $erreurs[] = 'Le téléphone doit contenir au moins 10 chiffres.';
    }

    if (mb_strlen($adresse) < 5) {
        $erreurs[] = 'L’adresse est trop courte.';
    }

    // Sur le profil, on ne change le mot de passe que si l'un des deux champs est rempli.
    $changerMdp = $motDePasseObligatoire || $motDePasse !== '' || $confirmation !== '';
    if ($changerMdp) {
        if (strlen($motDePasse) < PASSWORD_MIN_LENGTH) {
            $erreurs[] = 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères.';
        }
        if ($motDePasse !== $confirmation) {
            $erreurs[] = 'La confirmation du mot de passe ne correspond pas.';
        }
    }

    $propre = [
        'prenom' => $prenom,
        'nom' => $nom,
        'email' => $email,
        'telephone' => $telephone,
        'adresse' => $adresse,
        'mot_de_passe' => $motDePasse,
        'changer_mdp' => $changerMdp,
    ];

    return [$erreurs, $propre];
}

/**
 * Validation du formulaire produit côté admin.
 *
 * @return array{0: list<string>, 1: array<string, mixed>}
 */
function validateProductForm(array $data, array $categories): array
{
    $erreurs = [];

    $nom = trim((string) ($data['nom'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $prixBrut = str_replace(',', '.', trim((string) ($data['prix'] ?? '')));
    $stock = (int) ($data['stock'] ?? 0);
    $categoryId = (int) ($data['category_id'] ?? 0);
    $actif = isset($data['actif']) ? 1 : 0;

    if (mb_strlen($nom) < 2) {
        $erreurs[] = 'Le nom du produit doit contenir au moins 2 caractères.';
    }

    $categorieValide = false;
    foreach ($categories as $categorie) {
        if ((int) $categorie['id'] === $categoryId) {
            $categorieValide = true;
            break;
        }
    }
    if (!$categorieValide) {
        $erreurs[] = 'Choisis une catégorie existante.';
    }

    if (!is_numeric($prixBrut) || (float) $prixBrut < 0) {
        $erreurs[] = 'Le prix doit être un nombre positif.';
    }

    if ($stock < 0) {
        $erreurs[] = 'Le stock ne peut pas être négatif.';
    }

    $propre = [
        'nom' => $nom,
        'description' => $description,
        'prix' => $prixBrut,
        'stock' => $stock,
        'category_id' => $categoryId,
        'actif' => $actif,
    ];

    return [$erreurs, $propre];
}
