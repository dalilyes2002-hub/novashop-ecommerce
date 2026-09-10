<?php

declare(strict_types=1);

/**
 * Échappe le texte avant affichage : protection contre le XSS.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path): string
{
    return BASE_URL . $path;
}

function redirect(string $path): never
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function formatPrice(string|float $price): string
{
    return number_format((float) $price, 2, ',', ' ') . ' €';
}

function formatDate(?string $sqlDate, string $format = 'd/m/Y H:i'): string
{
    if ($sqlDate === null || $sqlDate === '') {
        return '';
    }

    return date($format, strtotime($sqlDate));
}

function productImageUrl(array $product): ?string
{
    $image = trim((string) ($product['image'] ?? ''));

    return $image === '' ? null : UPLOAD_URL . '/' . rawurlencode($image);
}

function productUrl(int $id): string
{
    return url('/product.php?id=' . $id);
}

function isAvailable(array $product): bool
{
    return (int) $product['actif'] === 1 && (int) $product['stock'] > 0;
}

function statusLabel(string $status): string
{
    return match ($status) {
        'en_attente' => 'En attente de paiement',
        'payee' => 'Payée',
        'expediee' => 'Expédiée',
        'livree' => 'Livrée',
        'annulee' => 'Annulée',
        default => $status,
    };
}

function paymentLabel(string $mode): string
{
    return match ($mode) {
        'carte' => 'Carte bancaire (fictive)',
        'livraison' => 'À la livraison',
        default => $mode,
    };
}

// --- Session et droits ---

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function currentUserId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['prenom'] = $user['prenom'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
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
        redirect('/client/account.php');
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
    redirect('/client/account.php');
}

// --- Messages flash ---

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

// --- Rendu des vues ---

/**
 * Charge une vue en lui passant des variables. Aucune requête SQL ici.
 */
function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../views/' . $view . '.php';
}
