<?php
$pageActive = basename((string) $_SERVER['SCRIPT_NAME']);

$liens = [
    'index.php' => 'Dashboard',
    'categories.php' => 'Catégories',
    'produits.php' => 'Produits',
    'clients.php' => 'Clients',
    'commandes.php' => 'Commandes',
];
?>
<nav class="admin-nav mb-4" aria-label="Navigation administration">
    <?php foreach ($liens as $fichier => $libelle): ?>
        <a class="admin-nav-link <?= $pageActive === $fichier ? 'active' : '' ?>"
           href="<?= e(ADMIN_URL) ?>/<?= e($fichier) ?>">
            <?= e($libelle) ?>
        </a>
    <?php endforeach; ?>
</nav>
