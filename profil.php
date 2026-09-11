<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$erreurs = [];

$stmt = $pdo->prepare(
    'SELECT prenom, nom, email, telephone, adresse FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/logout.php');
}

$old = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    [$erreurs, $clean] = validerCompte($_POST, false, $pdo, $userId);
    $old = array_merge($user, $clean);

    if ($erreurs === []) {
        if ($clean['changer_mdp']) {
            $hash = password_hash($clean['mot_de_passe'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'UPDATE users SET prenom = ?, nom = ?, email = ?, telephone = ?, adresse = ?, mot_de_passe = ? WHERE id = ?'
            );
            $stmt->execute([
                $clean['prenom'],
                $clean['nom'],
                $clean['email'],
                $clean['telephone'],
                $clean['adresse'],
                $hash,
                $userId,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users SET prenom = ?, nom = ?, email = ?, telephone = ?, adresse = ? WHERE id = ?'
            );
            $stmt->execute([
                $clean['prenom'],
                $clean['nom'],
                $clean['email'],
                $clean['telephone'],
                $clean['adresse'],
                $userId,
            ]);
        }

        $_SESSION['prenom'] = $clean['prenom'];
        $_SESSION['nom'] = $clean['nom'];
        $_SESSION['email'] = $clean['email'];
        setFlash('success', 'Profil mis à jour.');
        redirect('/profil.php');
    }
}

$pageTitle = 'Profil';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-card auth-card-wide">
    <h1 class="h3 mb-3">Mon profil</h1>

    <?php if ($erreurs): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= e($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(BASE_URL) ?>/profil.php" novalidate>
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
            <div class="col-12">
                <label class="form-label" for="adresse">Adresse</label>
                <input class="form-control" type="text" id="adresse" name="adresse" value="<?= e($old['adresse']) ?>" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="mot_de_passe">Nouveau mot de passe (optionnel)</label>
                <input class="form-control" type="password" id="mot_de_passe" name="mot_de_passe">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="mot_de_passe_confirm">Confirmation</label>
                <input class="form-control" type="password" id="mot_de_passe_confirm" name="mot_de_passe_confirm">
            </div>
        </div>
        <button class="btn btn-success mt-3" type="submit">Enregistrer</button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
