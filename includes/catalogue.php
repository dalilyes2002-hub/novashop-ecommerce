<?php

declare(strict_types=1);

function formatPrix(string|float $prix): string
{
    return number_format((float) $prix, 2, ',', ' ') . ' €';
}

function estDisponible(array $produit): bool
{
    return (int) $produit['actif'] === 1 && (int) $produit['stock'] > 0;
}

function urlProduit(int $id): string
{
    return BASE_URL . '/produit.php?id=' . $id;
}

function libelleStatut(string $statut): string
{
    return match ($statut) {
        'en_attente' => 'En attente de paiement',
        'payee' => 'Payée',
        'expediee' => 'Expédiée',
        'livree' => 'Livrée',
        'annulee' => 'Annulée',
        default => $statut,
    };
}

function libellePaiement(string $mode): string
{
    return match ($mode) {
        'carte' => 'Carte bancaire (fictive)',
        'livraison' => 'À la livraison',
        default => $mode,
    };
}

function imageProduit(array $produit): ?string
{
    $image = trim((string) ($produit['image'] ?? ''));

    return $image === '' ? null : BASE_URL . '/public/img/' . $image;
}
