document.addEventListener('DOMContentLoaded', () => {
    // Quantity controls for products
    document.querySelectorAll('.product-quantity-control').forEach((control) => {
        const input = control.querySelector('input[name="quantity"]');
        const minimum = parseInt(input.min, 10) || 1;

        control.querySelector('.quantity-decrease')?.addEventListener('click', () => {
            input.value = Math.max(minimum, (parseInt(input.value, 10) || minimum) - 1);
        });

        control.querySelector('.quantity-increase')?.addEventListener('click', () => {
            input.value = (parseInt(input.value, 10) || minimum) + 1;
        });
    });

    // Product Live Search & Sidebar Category Filter Interactivity
    const searchInput = document.getElementById('categorySearchInput');
    const sidebarCategoryBtns = document.querySelectorAll('.sidebar-category-btn');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const noResults = document.getElementById('noSearchResults');
    const categorySections = document.querySelectorAll('.product-category-section');

    let currentSelectedCategory = 'all';

    if (searchInput || sidebarCategoryBtns.length) {
        function updateSidebarActiveState(selectedCategory) {
            sidebarCategoryBtns.forEach((btn) => {
                const isMatch = btn.dataset.category === selectedCategory;
                btn.classList.toggle('active', isMatch);
                btn.classList.toggle('fw-bold', isMatch);
            });
        }

        function filterProducts() {
            const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
            let totalVisible = 0;

            categorySections.forEach((section) => {
                const groupCategory = section.dataset.categoryGroup || '';
                const matchesCategory = currentSelectedCategory === 'all' || groupCategory === currentSelectedCategory;
                const items = section.querySelectorAll('.product-item-col');
                let sectionVisibleCount = 0;

                items.forEach((item) => {
                    const name = item.dataset.productName || '';
                    const matchesSearch = !searchTerm || name.includes(searchTerm);

                    if (matchesCategory && matchesSearch) {
                        item.style.display = '';
                        sectionVisibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (matchesCategory && sectionVisibleCount > 0) {
                    section.style.display = '';
                    totalVisible += sectionVisibleCount;
                } else {
                    section.style.display = 'none';
                }
            });

            if (noResults) {
                noResults.classList.toggle('d-none', totalVisible > 0);
            }
        }

        if (sidebarCategoryBtns.length) {
            const sidebarCollapseEl = document.getElementById('categorySidebarCollapse');
            sidebarCategoryBtns.forEach((btn) => {
                btn.addEventListener('click', () => {
                    currentSelectedCategory = btn.dataset.category || 'all';
                    updateSidebarActiveState(currentSelectedCategory);
                    filterProducts();

                    // Close collapse menu on mobile view after selecting a category
                    if (sidebarCollapseEl && window.innerWidth < 992 && typeof bootstrap !== 'undefined') {
                        const bsCollapse = bootstrap.Collapse.getInstance(sidebarCollapseEl) || new bootstrap.Collapse(sidebarCollapseEl, { toggle: false });
                        bsCollapse.hide();
                    }
                });
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const val = e.target.value;
                if (clearSearchBtn) {
                    clearSearchBtn.style.display = val ? 'block' : 'none';
                }
                filterProducts();
            });

            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', () => {
                    searchInput.value = '';
                    clearSearchBtn.style.display = 'none';
                    filterProducts();
                });
            }
        }
    }

    // Back to Top Button Logic
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
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});




