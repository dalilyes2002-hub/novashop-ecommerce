<?php
$pageTitle = $pageTitle ?? 'Accueil';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> — NovaShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(BASE_URL) ?>/public/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-md navbar-dark bg-novashop">
    <div class="container-xl">
        <a class="navbar-brand fw-semibold" href="<?= e(BASE_URL) ?>/index.php">NovaShop</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(BASE_URL) ?>/index.php">Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(BASE_URL) ?>/produits.php">Produits</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(BASE_URL) ?>/categories.php">Catégories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(BASE_URL) ?>/panier.php">
                        Panier
                        <?php if (panierNbArticles() > 0): ?>
                            <span class="badge bg-light text-dark"><?= e((string) panierNbArticles()) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= e(BASE_URL) ?>/admin/index.php">Administration</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= e(BASE_URL) ?>/compte.php">Mon espace</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/commandes.php">Mes commandes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/profil.php">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/logout.php">Déconnexion</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/login.php">Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/register.php">Inscription</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="flex-grow-1">
    <div class="container-xl page-wrap">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type'] === 'success' ? 'success' : 'danger') ?>" role="alert">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
