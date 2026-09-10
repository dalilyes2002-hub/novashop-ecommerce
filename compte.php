<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

requireClient();

$pageTitle = 'Mon espace';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-3">Espace client</h1>
<p>Bonjour <?= e($_SESSION['prenom'] ?? '') ?> <?= e($_SESSION['nom'] ?? '') ?>.</p>
<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-success" href="<?= e(BASE_URL) ?>/commandes.php">Mes commandes</a>
    <a class="btn btn-outline-success" href="<?= e(BASE_URL) ?>/panier.php">Mon panier</a>
    <a class="btn btn-outline-secondary" href="<?= e(BASE_URL) ?>/profil.php">Modifier mon profil</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
