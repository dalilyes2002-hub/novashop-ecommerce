<?php

declare(strict_types=1);

// Point de départ de chaque page : session, réglages, helpers, base, modèles.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';

require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../models/category.php';
require_once __DIR__ . '/../models/product.php';
require_once __DIR__ . '/../models/order.php';
