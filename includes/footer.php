    </div>
</main>
<footer class="site-footer mt-auto">
    <div class="container-xl">
        <div class="row g-3 g-md-4">
            <div class="col-12 col-md-4">
                <h2 class="h6 text-white">NovaShop</h2>
                <p class="mb-0">Boutique en ligne — PC, périphériques et accessoires.</p>
            </div>
            <div class="col-6 col-md-4">
                <h2 class="h6 text-white">Navigation</h2>
                <a class="d-inline-block me-2" href="<?= e(BASE_URL) ?>/index.php">Accueil</a>
                <a class="d-inline-block me-2" href="<?= e(BASE_URL) ?>/produits.php">Produits</a>
                <a class="d-inline-block me-2" href="<?= e(BASE_URL) ?>/categories.php">Catégories</a>
                <?php if (!isLoggedIn()): ?>
                    <a class="d-inline-block me-2" href="<?= e(BASE_URL) ?>/login.php">Connexion</a>
                    <a class="d-inline-block" href="<?= e(BASE_URL) ?>/register.php">Inscription</a>
                <?php endif; ?>
            </div>
            <div class="col-6 col-md-4">
                <h2 class="h6 text-white">Contact</h2>
                <p class="mb-0">À venir</p>
            </div>
        </div>
        <p class="copyright mb-0">&copy; <?= e((string) date('Y')) ?> NovaShop</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
