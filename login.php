<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$destinations = ['checkout' => '/checkout.php', 'panier' => '/panier.php'];
$retour = (string) ($_POST['retour'] ?? $_GET['retour'] ?? '');
$destination = $destinations[$retour] ?? null;

function terminerConnexion(?string $destination): never
{
    if ($destination !== null && !isAdmin()) {
        redirect($destination);
    }
    redirectAfterLogin();
}

if (isLoggedIn()) {
    terminerConnexion($destination);
}

$erreur = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $email = trim($_POST['email'] ?? '');
    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');

    if ($email === '' || $motDePasse === '') {
        $erreur = 'Renseigne ton email et ton mot de passe.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'L’adresse email n’est pas valide.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, prenom, nom, email, mot_de_passe, role FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($motDePasse, $user['mot_de_passe'])) {
            $erreur = 'Email ou mot de passe incorrect.';
        } else {
            connecterUtilisateur($user);
            terminerConnexion($destination);
        }
    }
}

$pageTitle = 'Connexion';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-card">
    <h1 class="h3 mb-3">Connexion</h1>

    <?php if ($erreur !== ''): ?>
        <div class="alert alert-danger"><?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(BASE_URL) ?>/login.php" novalidate>
        <?= champCsrf() ?>
        <input type="hidden" name="retour" value="<?= e($retour) ?>">
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="mot_de_passe">Mot de passe</label>
            <input class="form-control" type="password" id="mot_de_passe" name="mot_de_passe" required>
        </div>
        <button class="btn btn-success" type="submit">Se connecter</button>
    </form>
    <p class="mt-3 mb-0 small">Pas encore de compte ? <a href="<?= e(BASE_URL) ?>/register.php">Inscription</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
