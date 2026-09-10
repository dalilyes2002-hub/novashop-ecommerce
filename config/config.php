<?php

declare(strict_types=1);

// Racine du site dans l'URL. À changer si tu renommes le dossier.
const BASE_URL = '/ecommerce';

const SHOP_NAME = 'NovaShop';

// Longueur minimale exigée à l'inscription.
const PASSWORD_MIN_LENGTH = 8;

// Quantité maximale d'un même produit dans le panier.
const CART_MAX_QUANTITY = 20;

// Images des produits : dossier sur le disque et URL correspondante.
const UPLOAD_DIR = __DIR__ . '/../uploads';
const UPLOAD_URL = BASE_URL . '/uploads';
const UPLOAD_MAX_BYTES = 2 * 1024 * 1024;
const UPLOAD_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

const ORDER_STATUSES = ['en_attente', 'payee', 'expediee', 'livree', 'annulee'];
const PAYMENT_MODES = ['carte', 'livraison'];
