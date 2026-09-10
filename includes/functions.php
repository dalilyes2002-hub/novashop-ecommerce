<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/ecommerce');
}

const PASSWORD_MIN_LENGTH = 8;

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        redirect('/compte.php');
    }
}

function requireClient(): void
{
    requireLogin();
    if (isAdmin()) {
        redirect('/admin/index.php');
    }
}

function redirectAfterLogin(): never
{
    if (isAdmin()) {
        redirect('/admin/index.php');
    }
    redirect('/compte.php');
}

function connecterUtilisateur(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['prenom'] = $user['prenom'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function emailExiste(PDO $pdo, string $email, ?int $saufId = null): bool
{
    if ($saufId === null) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $stmt->execute([$email, $saufId]);
    }

    return (bool) $stmt->fetch();
}

/**
 * @return array{0: list<string>, 1: array<string, string>}
 */
function validerCompte(array $data, bool $motDePasseObligatoire, PDO $pdo, ?int $userId = null): array
{
    $erreurs = [];

    $prenom = trim($data['prenom'] ?? '');
    $nom = trim($data['nom'] ?? '');
    $email = trim($data['email'] ?? '');
    $telephone = trim($data['telephone'] ?? '');
    $adresse = trim($data['adresse'] ?? '');
    $motDePasse = $data['mot_de_passe'] ?? '';
    $confirmation = $data['mot_de_passe_confirm'] ?? '';

    if ($prenom === '' || mb_strlen($prenom) < 2) {
        $erreurs[] = 'Le prénom doit contenir au moins 2 caractères.';
    }

    if ($nom === '' || mb_strlen($nom) < 2) {
        $erreurs[] = 'Le nom doit contenir au moins 2 caractères.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'L’adresse email n’est pas valide.';
    } elseif (emailExiste($pdo, $email, $userId)) {
        $erreurs[] = 'Cet email est déjà utilisé.';
    }

    $telDigits = preg_replace('/\D+/', '', $telephone) ?? '';
    if (strlen($telDigits) < 10) {
        $erreurs[] = 'Le téléphone doit contenir au moins 10 chiffres.';
    }

    if ($adresse === '' || mb_strlen($adresse) < 5) {
        $erreurs[] = 'L’adresse est trop courte.';
    }

    $changerMdp = $motDePasseObligatoire || $motDePasse !== '' || $confirmation !== '';
    if ($changerMdp) {
        if (strlen($motDePasse) < PASSWORD_MIN_LENGTH) {
            $erreurs[] = 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères.';
        }
        if ($motDePasse !== $confirmation) {
            $erreurs[] = 'La confirmation du mot de passe ne correspond pas.';
        }
    }

    $clean = [
        'prenom' => $prenom,
        'nom' => $nom,
        'email' => $email,
        'telephone' => $telephone,
        'adresse' => $adresse,
        'mot_de_passe' => $motDePasse,
        'changer_mdp' => $changerMdp,
    ];

    return [$erreurs, $clean];
}

require_once __DIR__ . '/cart.php';
