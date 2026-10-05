    <?php if ($isAdminArea && empty($isStandalonePage)): ?>
            </div> <!-- End admin-content -->
        </div> <!-- End row -->
    <?php endif; ?>
    </main>

    <?php if (empty($isStandalonePage)): ?>
    <footer class="site-footer bg-dark text-white mt-5 pt-5 pb-4 position-relative overflow-hidden" style="border-top: 4px solid var(--bs-primary);">
        <!-- Decorative background element -->
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.1), transparent 50%); pointer-events: none;"></div>
        
        <div class="container position-relative z-1">
            <div class="row g-4 g-lg-5 align-items-start pb-5 border-bottom border-secondary mb-4">
                
                <!-- Brand and About -->
                <div class="col-12 col-lg-4">
                    <div class="footer-brand d-flex align-items-center gap-3 mb-4">
                        <img src="<?= asset('img/shantini_logo.png') ?>" alt="Shantini Crackers Logo" style="height: 48px; width: auto; border-radius: 8px;" class="shadow-sm">
                        <span class="fs-3 fw-bold text-white" style="font-family: var(--font-heading); letter-spacing: -0.5px;">Shantini Crackers</span>
                    </div>
                    <p class="text-white-50 lh-lg pe-lg-4 mb-4">
                        Premium fireworks and festive celebration products. Lighting up your special moments with joy, safety, and spectacular colors.
                    </p>
                    
                    <!-- Social Links -->
                    <div class="d-flex gap-3">
                        <a href="https://www.facebook.com/share/1AGTaToTGZ/" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center transition hover-primary border-secondary text-white-50" style="width: 36px; height: 36px;" aria-label="Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="https://www.instagram.com/shantinicrackersivakasi/" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center transition hover-primary border-secondary text-white-50" style="width: 36px; height: 36px;" aria-label="Instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="https://wa.me/917708971956?text=Hello%20Shantini%20Crackers%2C%20I%20need%20help%20with%20my%20fireworks%20order." target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center transition hover-primary border-secondary text-white-50" style="width: 36px; height: 36px;" aria-label="WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-6 col-md-4 col-lg-2">
                    <h5 class="fw-bold mb-4 text-white text-uppercase" style="font-family: var(--font-heading); font-size: 1rem; letter-spacing: 1px;">Quick Links</h5>
                    <ul class="list-unstyled footer-links mb-0 d-flex flex-column gap-3">
                        <li><a href="<?= url('home') ?>" class="text-white-50 text-decoration-none hover-primary transition d-flex align-items-center gap-2"><i class="fa-solid fa-angle-right small text-primary"></i> Home</a></li>
                        <li><a href="<?= url('products') ?>" class="text-white-50 text-decoration-none hover-primary transition d-flex align-items-center gap-2"><i class="fa-solid fa-angle-right small text-primary"></i> Products</a></li>
                        <li><a href="<?= url('about') ?>" class="text-white-50 text-decoration-none hover-primary transition d-flex align-items-center gap-2"><i class="fa-solid fa-angle-right small text-primary"></i> About Us</a></li>
                        <li><a href="<?= url('contact') ?>" class="text-white-50 text-decoration-none hover-primary transition d-flex align-items-center gap-2"><i class="fa-solid fa-angle-right small text-primary"></i> Contact</a></li>
                    </ul>
                </div>

                <!-- Legal Links -->
                <div class="col-6 col-md-4 col-lg-2">
                    <h5 class="fw-bold mb-4 text-white text-uppercase" style="font-family: var(--font-heading); font-size: 1rem; letter-spacing: 1px;">Legal</h5>
                    <ul class="list-unstyled footer-links mb-0 d-flex flex-column gap-3">
                        <li><a href="<?= url('terms') ?>" class="text-white-50 text-decoration-none hover-primary transition d-flex align-items-center gap-2"><i class="fa-solid fa-angle-right small text-primary"></i> Terms of Use</a></li>
                        <li><a href="<?= url('privacy') ?>" class="text-white-50 text-decoration-none hover-primary transition d-flex align-items-center gap-2"><i class="fa-solid fa-angle-right small text-primary"></i> Privacy Policy</a></li>
                        <li><a href="<?= url('shipping') ?>" class="text-white-50 text-decoration-none hover-primary transition d-flex align-items-center gap-2"><i class="fa-solid fa-angle-right small text-primary"></i> Shipping & Delivery</a></li>
                        <li><a href="<?= url('refund') ?>" class="text-white-50 text-decoration-none hover-primary transition d-flex align-items-center gap-2"><i class="fa-solid fa-angle-right small text-primary"></i> Refund Policy</a></li>
                    </ul>
                </div>

                <!-- Contact & CTA -->
                <div class="col-12 col-md-4 col-lg-4">
                    <h5 class="fw-bold mb-4 text-white text-uppercase" style="font-family: var(--font-heading); font-size: 1rem; letter-spacing: 1px;">Contact Us</h5>
                    <ul class="list-unstyled footer-links mb-4 d-flex flex-column gap-3">
                        <li class="d-flex align-items-start gap-3">
                            <i class="fa-solid fa-location-dot text-primary mt-1"></i>
                            <span class="text-white-50">Sivakasi, Tamil Nadu<br>India</span>
                        </li>
                        <li>
                            <a href="tel:9786361678" class="text-white-50 text-decoration-none hover-primary transition d-flex align-items-center gap-3">
                                <i class="fa-solid fa-phone text-primary"></i> 9786361678
                            </a>
                        </li>
                        <li>
                            <a href="mailto:shantinicrackerssivakasi@gmail.com" class="text-white-50 text-decoration-none hover-primary transition d-flex align-items-center gap-3">
                                <i class="fa-solid fa-envelope text-primary"></i> shantinicrackerssivakasi@gmail.com
                            </a>
                        </li>
                    </ul>
                    
                    <a href="tel:9786361678" class="btn btn-primary w-100 text-center d-flex justify-content-center align-items-center gap-2 py-2 shadow-sm rounded-pill fw-bold text-uppercase" style="letter-spacing: 1px;">
                        <i class="fa-solid fa-phone-volume"></i> Call Now
                    </a>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="text-center d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
                <p class="text-white-50 small mb-0">&copy; <?= date('Y') ?> Shantini Crackers. All Rights Reserved.</p>
                <div class="text-white-50 small d-flex align-items-center gap-2">
                    <span>Designed with <i class="fa-solid fa-heart text-danger mx-1"></i> for festive joy.</span>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Cookie Consent Banner -->
    <div id="cookieConsentBanner" class="cookie-consent-banner">
        <div class="container">
            <div class="banner-content">
                <p class="cookie-consent-text">
                    We use cookies to analyze website traffic and optimize your website experience. By accepting our use of cookies, your data will be aggregated with all other user data. <a href="<?= url('privacy') ?>" class="text-primary text-decoration-underline">Learn more</a>
                </p>
                <div class="cookie-consent-actions">
                    <button id="btnCookieDecline" class="btn-cookie-decline">Decline</button>
                    <button id="btnCookieAccept" class="btn-cookie-accept">Accept All</button>
                </div>
            </div>
        </div>
    </div>

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
    <script>
    // AJAX for adding items to cart to prevent page reload and scroll jump
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.tagName === 'FORM' && (form.getAttribute('action') === 'index.php?page=cart' || form.getAttribute('action') === '<?= url("cart") ?>')) {
            const actionInput = form.querySelector('input[name="action"]');
            if (actionInput && actionInput.value === 'add') {
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
                submitBtn.disabled = true;

                const formData = new FormData(form);
                fetch('index.php?page=cart', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.text())
                .then(html => {
                    submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Added';
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 2000);
                    
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newCartLink = doc.getElementById('navbarCartLink');
                    const currentCartLink = document.getElementById('navbarCartLink');
                    if (newCartLink && currentCartLink) {
                        currentCartLink.innerHTML = newCartLink.innerHTML;
                    }
                })
                .catch(error => {
                    console.error('Error adding to cart:', error);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }
        }
    });
    </script>
    
    <!-- Google Analytics Consent & Integration -->
    <script>
        (function() {
            const banner = document.getElementById('cookieConsentBanner');
            const btnAccept = document.getElementById('btnCookieAccept');
            const btnDecline = document.getElementById('btnCookieDecline');
            
            const gaMeasurementId = 'G-PSL8LT8LFF';
            
            function loadGoogleAnalytics() {
                const script = document.createElement('script');
                script.async = true;
                script.src = `https://www.googletagmanager.com/gtag/js?id=${gaMeasurementId}`;
                document.head.appendChild(script);

                window.dataLayer = window.dataLayer || [];
                function gtag(){window.dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', gaMeasurementId);
            }

            if (banner && btnAccept && btnDecline) {
                const consent = localStorage.getItem('ga_consent');
                
                if (consent === 'accepted') {
                    loadGoogleAnalytics();
                } else if (!consent) {
                    setTimeout(() => {
                        banner.classList.add('show');
                    }, 500);
                }

                btnAccept.addEventListener('click', function() {
                    localStorage.setItem('ga_consent', 'accepted');
                    banner.classList.remove('show');
                    loadGoogleAnalytics();
                });

                btnDecline.addEventListener('click', function() {
                    localStorage.setItem('ga_consent', 'declined');
                    banner.classList.remove('show');
                });
            }
        })();
    </script>
</body>
</html>
