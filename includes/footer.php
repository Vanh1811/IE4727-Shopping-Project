    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a class="brand brand--footer" href="index.php">
                    <span class="brand-mark" aria-hidden="true">F2B</span>
                    <span>F2B SHOP</span>
                </a>
                <p>Thoughtful essentials and expressive pieces for every version of you.</p>
            </div>

            <div class="footer-column">
                <h2>Company</h2>
                <a href="orders.php">Order status</a>
            </div>

            <div class="footer-column">
                <h2>Shop</h2>
                <a href="shop.php">All products</a>
                <a href="index.php#new-arrivals">New arrivals</a>
                <a href="index.php#styles">Browse styles</a>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; <?= date('Y') ?> F2B SHOP.</p>
        </div>
    </footer>
    <script src="js/main.js?v=<?= (int) filemtime(__DIR__ . '/../js/main.js') ?>"></script>
</body>
</html>
