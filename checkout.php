<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/catalogue.php';
require_once __DIR__ . '/config/database.php';

// Règle du sujet : on ne commande pas sans compte.
requireLogin();

$userId = (int) $_SESSION['user_id'];

$lignes = panierLignes($pdo);
if (!panierEstCommandable($lignes)) {
    setFlash('danger', 'Ton panier est vide ou une quantité dépasse le stock.');
    redirect('/panier.php');
}
$total = panierTotal($lignes);

$etapesValides = ['adresse', 'recap', 'paiement'];
$etape = (string) ($_GET['etape'] ?? 'adresse');
if (!in_array($etape, $etapesValides, true)) {
    $etape = 'adresse';
}

$erreurs = [];

// Adresse par défaut : celle du profil.
if (!isset($_SESSION['checkout_adresse'])) {
    $stmt = $pdo->prepare('SELECT adresse FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $_SESSION['checkout_adresse'] = (string) $stmt->fetchColumn();
}
$adresse = (string) $_SESSION['checkout_adresse'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etapeSoumise = (string) ($_POST['etape'] ?? '');

    if ($etapeSoumise === 'adresse') {
        $adresse = trim((string) ($_POST['adresse_livraison'] ?? ''));
        if (mb_strlen($adresse) < 5) {
            $erreurs[] = 'L’adresse de livraison est trop courte.';
            $etape = 'adresse';
        } else {
            $_SESSION['checkout_adresse'] = $adresse;
            redirect('/checkout.php?etape=recap');
        }
    }

    if ($etapeSoumise === 'recap') {
        redirect('/checkout.php?etape=paiement');
    }

    if ($etapeSoumise === 'paiement') {
        $modePaiement = (string) ($_POST['mode_paiement'] ?? '');
        if (!in_array($modePaiement, ['carte', 'livraison'], true)) {
            $erreurs[] = 'Choisis un mode de paiement.';
            $etape = 'paiement';
        } elseif (mb_strlen($adresse) < 5) {
            $erreurs[] = 'Adresse de livraison manquante.';
            $etape = 'adresse';
        } else {
            // Paiement simulé : aucun numéro de carte n'est demandé ni stocké.
            $statut = $modePaiement === 'carte' ? 'payee' : 'en_attente';

            try {
                $pdo->beginTransaction();

                // On relit les stocks dans la transaction et on verrouille les lignes.
                $ids = array_column($lignes, 'id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare(
                    'SELECT id, prix, stock FROM products
                     WHERE id IN (' . $placeholders . ') AND actif = 1
                     FOR UPDATE'
                );
                $stmt->execute($ids);
                $produitsVerrouilles = [];
                foreach ($stmt->fetchAll() as $produit) {
                    $produitsVerrouilles[(int) $produit['id']] = $produit;
                }

                $totalCalcule = 0.0;
                foreach ($lignes as $ligne) {
                    $produit = $produitsVerrouilles[$ligne['id']] ?? null;
                    if ($produit === null) {
                        throw new RuntimeException('Le produit « ' . $ligne['nom'] . ' » n’est plus disponible.');
                    }
                    if ((int) $produit['stock'] < $ligne['quantite']) {
                        throw new RuntimeException('Stock insuffisant pour « ' . $ligne['nom'] . ' ».');
                    }
                    // Le prix de référence est celui de la base, pas celui de la session.
                    $totalCalcule += (float) $produit['prix'] * $ligne['quantite'];
                }
                $totalCalcule = round($totalCalcule, 2);

                $stmt = $pdo->prepare(
                    'INSERT INTO orders (user_id, total, adresse_livraison, mode_paiement, statut)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$userId, $totalCalcule, $adresse, $modePaiement, $statut]);
                $orderId = (int) $pdo->lastInsertId();

                $stmtLigne = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, quantite, prix_unitaire)
                     VALUES (?, ?, ?, ?)'
                );
                $stmtStock = $pdo->prepare(
                    'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?'
                );

                foreach ($lignes as $ligne) {
                    $prixUnitaire = (float) $produitsVerrouilles[$ligne['id']]['prix'];
                    $stmtLigne->execute([$orderId, $ligne['id'], $ligne['quantite'], $prixUnitaire]);
                    $stmtStock->execute([$ligne['quantite'], $ligne['id'], $ligne['quantite']]);

                    if ($stmtStock->rowCount() !== 1) {
                        throw new RuntimeException('Le stock de « ' . $ligne['nom'] . ' » a changé.');
                    }
                }

                $pdo->commit();

                panierVider();
                unset($_SESSION['checkout_adresse']);
                setFlash('success', 'Commande n°' . $orderId . ' enregistrée.');
                redirect('/commande.php?id=' . $orderId);
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $erreurs[] = $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'La commande n’a pas pu être enregistrée.';
                $etape = 'paiement';
            }
        }
    }
}

$numeroEtape = (int) array_search($etape, $etapesValides, true) + 1;

$pageTitle = 'Commande';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-1">Commande</h1>
<p class="text-muted">Étape <?= e((string) $numeroEtape) ?> sur 3</p>

<?php if ($erreurs): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($erreurs as $erreur): ?>
                <li><?= e($erreur) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($etape === 'adresse'): ?>
    <div class="auth-card auth-card-wide">
        <h2 class="h5 mb-3">1. Adresse de livraison</h2>
        <form method="post" action="<?= e(BASE_URL) ?>/checkout.php">
            <input type="hidden" name="etape" value="adresse">
            <div class="mb-3">
                <label class="form-label" for="adresse_livraison">Adresse complète</label>
                <textarea class="form-control" id="adresse_livraison" name="adresse_livraison" rows="3" required><?= e($adresse) ?></textarea>
                <div class="form-text">Pré-remplie avec l’adresse de ton profil, tu peux la modifier.</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="<?= e(BASE_URL) ?>/panier.php">Retour au panier</a>
                <button class="btn btn-success" type="submit">Continuer</button>
            </div>
        </form>
    </div>

<?php elseif ($etape === 'recap'): ?>
    <h2 class="h5 mb-3">2. Récapitulatif</h2>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Produit</th>
                    <th scope="col">Prix unitaire</th>
                    <th scope="col">Quantité</th>
                    <th scope="col">Sous-total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><?= e($ligne['nom']) ?></td>
                        <td><?= e(formatPrix($ligne['prix'])) ?></td>
                        <td><?= e((string) $ligne['quantite']) ?></td>
                        <td><?= e(formatPrix($ligne['sous_total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th><?= e(formatPrix($total)) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="info-card mb-3">
        <h3 class="h6">Livraison à</h3>
        <p class="mb-0"><?= nl2br(e($adresse)) ?></p>
    </div>

    <form method="post" action="<?= e(BASE_URL) ?>/checkout.php">
        <input type="hidden" name="etape" value="recap">
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(BASE_URL) ?>/checkout.php?etape=adresse">Modifier l’adresse</a>
            <button class="btn btn-success" type="submit">Continuer vers le paiement</button>
        </div>
    </form>

<?php else: ?>
    <div class="auth-card auth-card-wide">
        <h2 class="h5 mb-3">3. Paiement</h2>
        <p>Montant à payer : <strong><?= e(formatPrix($total)) ?></strong></p>

        <form method="post" action="<?= e(BASE_URL) ?>/checkout.php">
            <input type="hidden" name="etape" value="paiement">

            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_carte" value="carte" checked>
                <label class="form-check-label" for="paiement_carte">
                    Carte bancaire (paiement fictif, aucune donnée bancaire demandée)
                </label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_livraison" value="livraison">
                <label class="form-check-label" for="paiement_livraison">
                    Paiement à la livraison
                </label>
            </div>

            <div class="alert alert-secondary small">
                Boutique de démonstration : aucun paiement réel n’est effectué et aucun numéro de carte n’est saisi.
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="<?= e(BASE_URL) ?>/checkout.php?etape=recap">Retour</a>
                <button class="btn btn-success" type="submit">Valider ma commande</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
