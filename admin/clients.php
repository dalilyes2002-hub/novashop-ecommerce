<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$recherche = trim((string) ($_GET['q'] ?? ''));

$sql = "SELECT u.id, u.prenom, u.nom, u.email, u.telephone, u.role, u.created_at,
               COUNT(o.id) AS nb_commandes,
               COALESCE(SUM(CASE WHEN o.statut != 'annulee' THEN o.total ELSE 0 END), 0) AS total_achats
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.id
        WHERE u.role = 'client'";
$params = [];

if ($recherche !== '') {
    $sql .= ' AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
}

$sql .= ' GROUP BY u.id, u.prenom, u.nom, u.email, u.telephone, u.role, u.created_at
          ORDER BY u.nom, u.prenom';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

$pageTitle = 'Clients (admin)';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<h1 class="h3 mb-3">Clients</h1>

<form class="filters mb-4" method="get" action="<?= e(ADMIN_URL) ?>/clients.php">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-6">
            <label class="form-label" for="q">Rechercher</label>
            <input class="form-control" type="search" id="q" name="q" value="<?= e($recherche) ?>"
                   placeholder="Nom, prénom ou email">
        </div>
        <div class="col-12 col-md-6 d-flex gap-2">
            <button class="btn btn-success" type="submit">Rechercher</button>
            <a class="btn btn-outline-secondary" href="<?= e(ADMIN_URL) ?>/clients.php">Réinitialiser</a>
        </div>
    </div>
</form>

<p class="text-muted"><?= e((string) count($clients)) ?> client(s)</p>

<?php if ($clients === []): ?>
    <div class="alert alert-secondary">Aucun client trouvé.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Client</th>
                    <th scope="col">Email</th>
                    <th scope="col">Commandes</th>
                    <th scope="col">Total achats</th>
                    <th scope="col"><span class="visually-hidden">Détail</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td>
                            <?= e($client['prenom'] . ' ' . $client['nom']) ?>
                            <div class="small text-muted">
                                Inscrit le <?= e(date('d/m/Y', strtotime((string) $client['created_at']))) ?>
                            </div>
                        </td>
                        <td><?= e($client['email']) ?></td>
                        <td><?= e((string) $client['nb_commandes']) ?></td>
                        <td><?= e(formatPrix($client['total_achats'])) ?></td>
                        <td class="text-end">
                            <a class="btn btn-outline-success btn-sm"
                               href="<?= e(ADMIN_URL) ?>/client.php?id=<?= e((string) $client['id']) ?>">
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
