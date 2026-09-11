<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    $orderId = (int) ($_POST['id'] ?? 0);
    $statut = (string) ($_POST['statut'] ?? '');
    $retourFiltre = (string) ($_POST['filtre'] ?? '');

    $message = changerStatutCommande($pdo, $orderId, $statut);

    if ($message === '') {
        setFlash('success', 'Commande n°' . $orderId . ' : statut mis à jour.');
    } else {
        setFlash('danger', $message);
    }

    $suffixe = in_array($retourFiltre, statutsCommande(), true) ? '?statut=' . $retourFiltre : '';
    redirect('/admin/commandes.php' . $suffixe);
}

$filtreStatut = (string) ($_GET['statut'] ?? '');
if (!in_array($filtreStatut, statutsCommande(), true)) {
    $filtreStatut = '';
}

$sql = 'SELECT o.id, o.total, o.statut, o.mode_paiement, o.created_at,
               u.id AS client_id, u.prenom, u.nom,
               COUNT(oi.id) AS nb_lignes
        FROM orders o
        INNER JOIN users u ON u.id = o.user_id
        LEFT JOIN order_items oi ON oi.order_id = o.id';
$params = [];

if ($filtreStatut !== '') {
    $sql .= ' WHERE o.statut = ?';
    $params[] = $filtreStatut;
}

$sql .= ' GROUP BY o.id, o.total, o.statut, o.mode_paiement, o.created_at, u.id, u.prenom, u.nom
          ORDER BY o.created_at DESC, o.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

$pageTitle = 'Commandes (admin)';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<h1 class="h3 mb-3">Commandes</h1>

<form class="filters mb-4" method="get" action="<?= e(ADMIN_URL) ?>/commandes.php">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-6">
            <label class="form-label" for="statut">Filtrer par statut</label>
            <select class="form-select" id="statut" name="statut">
                <option value="">Tous</option>
                <?php foreach (statutsCommande() as $statut): ?>
                    <option value="<?= e($statut) ?>" <?= $filtreStatut === $statut ? 'selected' : '' ?>>
                        <?= e(libelleStatut($statut)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-6 d-flex gap-2">
            <button class="btn btn-success" type="submit">Filtrer</button>
            <a class="btn btn-outline-secondary" href="<?= e(ADMIN_URL) ?>/commandes.php">Tout</a>
        </div>
    </div>
</form>

<p class="text-muted"><?= e((string) count($commandes)) ?> commande(s)</p>

<?php if ($commandes === []): ?>
    <div class="alert alert-secondary">Aucune commande pour ce filtre.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">N°</th>
                    <th scope="col">Date</th>
                    <th scope="col">Client</th>
                    <th scope="col">Articles</th>
                    <th scope="col">Total</th>
                    <th scope="col">Statut</th>
                    <th scope="col"><span class="visually-hidden">Détail</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $commande): ?>
                    <tr>
                        <td><?= e((string) $commande['id']) ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime((string) $commande['created_at']))) ?></td>
                        <td>
                            <a href="<?= e(ADMIN_URL) ?>/client.php?id=<?= e((string) $commande['client_id']) ?>">
                                <?= e($commande['prenom'] . ' ' . $commande['nom']) ?>
                            </a>
                        </td>
                        <td><?= e((string) $commande['nb_lignes']) ?></td>
                        <td><?= e(formatPrix($commande['total'])) ?></td>
                        <td>
                            <form class="d-flex gap-2" method="post" action="<?= e(ADMIN_URL) ?>/commandes.php">
                                <?= champCsrf() ?>
                                <input type="hidden" name="id" value="<?= e((string) $commande['id']) ?>">
                                <input type="hidden" name="filtre" value="<?= e($filtreStatut) ?>">
                                <select class="form-select form-select-sm" name="statut"
                                        aria-label="Statut de la commande <?= e((string) $commande['id']) ?>">
                                    <?php foreach (statutsCommande() as $statut): ?>
                                        <option value="<?= e($statut) ?>" <?= $commande['statut'] === $statut ? 'selected' : '' ?>>
                                            <?= e(libelleStatut($statut)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-secondary btn-sm" type="submit">OK</button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-outline-success btn-sm"
                               href="<?= e(ADMIN_URL) ?>/commande.php?id=<?= e((string) $commande['id']) ?>">
                                Détail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
