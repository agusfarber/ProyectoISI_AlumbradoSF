// Funcionalidad del sidebar móvil / topbar de cuenta
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebar) {
        sidebar.style.animation = 'none';
        sidebar.style.opacity = '1';
        sidebar.style.visibility = 'visible';
        sidebar.style.transform = window.innerWidth <= 768 ? 'translateY(0)' : 'translateX(0)';
    }

    function toggleSidebar() {
        if (!sidebar || !sidebarOverlay) return;
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (sidebar && window.innerWidth > 768 && sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });
    });

    const dropdownItems = document.querySelectorAll('.sidebar .dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function() {
            if (sidebar && window.innerWidth > 768 && sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });
    });

    // Menús de cuenta (sidebar escritorio/móvil y topbar admin/operario)
    const userMenus = document.querySelectorAll('.user-menu');
    if (userMenus.length) {
        const cerrarTodos = () => {
            userMenus.forEach(menu => {
                menu.classList.remove('open');
                const toggle = menu.querySelector('.user-menu__toggle');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            });
        };

        userMenus.forEach(menu => {
            const toggle = menu.querySelector('.user-menu__toggle');
            if (!toggle) return;
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                const estabaAbierto = menu.classList.contains('open');
                cerrarTodos();
                if (!estabaAbierto) {
                    menu.classList.add('open');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            });
        });

        document.addEventListener('click', function (e) {
            const dentro = Array.from(userMenus).some(menu => menu.contains(e.target));
            if (!dentro) cerrarTodos();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') cerrarTodos();
        });
    }

    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar .nav-link');

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (!href) return;
        const currentPage = currentPath.split('/').filter(p => p).pop();
        const linkPage = href.split('/').filter(p => p).pop();
        if (currentPage === linkPage) {
            link.classList.add('active');
        }
    });

    const dropdownToggles = document.querySelectorAll('.sidebar .dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        const dropdown = toggle.nextElementSibling;
        if (!dropdown) return;
        const dropdownLinks = dropdown.querySelectorAll('.dropdown-item');

        dropdownLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (!href) return;
            const currentPage = currentPath.split('/').filter(p => p).pop();
            const linkPage = href.split('/').filter(p => p).pop();
            if (currentPage === linkPage) {
                toggle.classList.add('active');
            }
        });
    });
});

window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    if (!sidebar) return;

    if (window.innerWidth > 768) {
        sidebar.classList.remove('show');
        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('show');
        }
    }
});
