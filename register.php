<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    redirectAfterLogin();
}

$erreurs = [];
$old = [
    'prenom' => '',
    'nom' => '',
    'email' => '',
    'telephone' => '',
    'adresse' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    [$erreurs, $clean] = validerCompte($_POST, true, $pdo);
    $old = $clean;

    if ($erreurs === []) {
        $hash = password_hash($clean['mot_de_passe'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (prenom, nom, email, telephone, adresse, mot_de_passe, role)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $clean['prenom'],
            $clean['nom'],
            $clean['email'],
            $clean['telephone'],
            $clean['adresse'],
            $hash,
            'client',
        ]);

        $stmt = $pdo->prepare('SELECT id, prenom, nom, email, role FROM users WHERE id = ?');
        $stmt->execute([(int) $pdo->lastInsertId()]);
        $user = $stmt->fetch();
        if ($user) {
            connecterUtilisateur($user);
            setFlash('success', 'Compte créé. Bienvenue sur NovaShop.');
            redirectAfterLogin();
        }
    }
}

$pageTitle = 'Inscription';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-card auth-card-wide">
    <h1 class="h3 mb-3">Inscription</h1>

    <?php if ($erreurs): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= e($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(BASE_URL) ?>/register.php" novalidate>
        <?= champCsrf() ?>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="prenom">Prénom</label>
                <input class="form-control" type="text" id="prenom" name="prenom" value="<?= e($old['prenom']) ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="nom">Nom</label>
                <input class="form-control" type="text" id="nom" name="nom" value="<?= e($old['nom']) ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="telephone">Téléphone</label>
                <input class="form-control" type="tel" id="telephone" name="telephone" value="<?= e($old['telephone']) ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="adresse">Adresse</label>
                <input class="form-control" type="text" id="adresse" name="adresse" value="<?= e($old['adresse']) ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="mot_de_passe">Mot de passe (min. <?= e((string) PASSWORD_MIN_LENGTH) ?>)</label>
                <input class="form-control" type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="mot_de_passe_confirm">Confirmation</label>
                <input class="form-control" type="password" id="mot_de_passe_confirm" name="mot_de_passe_confirm" required>
            </div>
        </div>
        <button class="btn btn-success mt-3" type="submit">Créer mon compte</button>
    </form>
    <p class="mt-3 mb-0 small">Déjà inscrit ? <a href="<?= e(BASE_URL) ?>/login.php">Connexion</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
