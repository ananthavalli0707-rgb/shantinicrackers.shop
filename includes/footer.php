    <?php if ($isAdminArea): ?>
            </div> <!-- End admin-content -->
        </div> <!-- End row -->
    <?php endif; ?>
    </main>

    <footer class="site-footer mt-5 pt-5 pb-3">
        <div class="container">
            <div class="row g-4 g-lg-5 align-items-start pb-5">
                <div class="col-12 col-lg-4">
                    <div class="footer-brand d-flex align-items-center gap-2 mb-3">
                        <span style="font-size: 1.5rem;">🎆</span>
                        <span class="fs-4 fw-bold text-white" style="font-family: var(--font-heading);">Shantini Crackers</span>
                    </div>
                    <p class="text-white-50 lh-base">Premium fireworks and festive celebration products for homes, events, and memorable celebrations.</p>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <h5 class="fw-bold mb-4 text-white" style="font-family: var(--font-heading);">Quick Links</h5>
                    <ul class="list-unstyled footer-links mb-0 d-flex flex-column gap-3">
                        <li><a href="<?= url('home') ?>" class="text-white-50 text-decoration-none hover-primary transition">Home</a></li>
                        <li><a href="<?= url('products') ?>" class="text-white-50 text-decoration-none hover-primary transition">Products</a></li>
                        <li><a href="<?= url('about') ?>" class="text-white-50 text-decoration-none hover-primary transition">About Us</a></li>
                        <li><a href="<?= url('contact') ?>" class="text-white-50 text-decoration-none hover-primary transition">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <h5 class="fw-bold mb-4 text-white" style="font-family: var(--font-heading);">Contact</h5>
                    <ul class="list-unstyled footer-links mb-0 d-flex flex-column gap-3">
                        <li><a href="tel:9786361678" class="text-white-50 text-decoration-none hover-primary transition"><i class="fa-solid fa-phone me-2 text-primary"></i> 9786361678</a></li>
                        <li><a href="mailto:shantinicrackerssivakasi@gmail.com" class="text-white-50 text-decoration-none hover-primary transition"><i class="fa-solid fa-envelope me-2 text-primary"></i> Email Us</a></li>
                    </ul>
                </div>
                <div class="col-12 col-md-4 col-lg-2">
                    <a href="tel:9786361678" class="btn-primary w-100 text-center d-block text-decoration-none py-3 shadow-sm">
                        <i class="fa-solid fa-phone me-2"></i> Call Now
                    </a>
                </div>
            </div>
            <div class="border-top border-color pt-4 text-center text-lg-start d-lg-flex justify-content-between align-items-center">
                <p class="text-white-50 small mb-0">&copy; <?= date('Y') ?> Shantini Crackers. All Rights Reserved.</p>
                <p class="text-white-50 small mb-0 mt-2 mt-lg-0">Product details and estimates only.</p>
            </div>
        </div>
    </footer>

    <button type="button" class="back-to-top-btn" id="backToTopBtn" aria-label="Back to top">
        <i class="fa-solid fa-arrow-up"></i> Back to Top
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const backToTopBtn = document.getElementById('backToTopBtn');
        if (backToTopBtn) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    backToTopBtn.classList.add('show');
                } else {
                    backToTopBtn.classList.remove('show');
                }
            });
            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    </script>
    <script>
        (function() {
            const body = document.body;
            const toggle = document.getElementById('themeToggle');
            const updateTheme = function(theme) {
                if (!theme) {
                    theme = localStorage.getItem('shantini-theme') || 'light';
                }
                body.setAttribute('data-theme', theme);
                if (toggle) {
                    const isDark = theme === 'dark';
                    toggle.querySelector('.theme-icon').textContent = isDark ? '☀️' : '🌙';
                }
            };

            if (toggle) {
                const supportedTheme = localStorage.getItem('shantini-theme') || 'light';
                updateTheme(supportedTheme);
                toggle.addEventListener('click', function() {
                    const currentTheme = body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('shantini-theme', currentTheme);
                    updateTheme(currentTheme);
                });
            }
        })();
    </script>
    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
