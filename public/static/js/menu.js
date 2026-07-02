// Funcionalidad del sidebar móvil
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Asegurar que el sidebar esté siempre visible desde el inicio (sin animaciones)
    if (sidebar) {
        sidebar.style.animation = 'none';
        sidebar.style.opacity = '1';
        sidebar.style.visibility = 'visible';
        sidebar.style.transform = window.innerWidth <= 768 ? 'translateY(0)' : 'translateX(0)';
    }

    // Función para abrir/cerrar el sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    }

    // Event listener para el botón toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    // Event listener para cerrar con el overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // En móvil el menú está siempre visible arriba, no se cierra al hacer clic
    // Solo cerrar sidebar en desktop si está abierto (aunque no debería pasar)
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            // No hacer nada en móvil, el menú está siempre visible
            // Solo en desktop si por alguna razón el sidebar está abierto
            if (window.innerWidth > 768 && sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });
    });

    // Cerrar sidebar al hacer clic en dropdown items (solo desktop)
    const dropdownItems = document.querySelectorAll('.sidebar .dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth > 768 && sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        });
    });

    // Menús desplegables de la cuenta del usuario (Perfil / Cerrar sesión)
    // Soporta múltiples instancias (escritorio y móvil)
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

    // Marcar enlace activo basado en la URL actual
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href) {
            // Obtener la última parte de la URL actual y del href
            const currentPage = currentPath.split('/').filter(p => p).pop();
            const linkPage = href.split('/').filter(p => p).pop();
            
            // Comparación exacta (no parcial)
            if (currentPage === linkPage) {
                link.classList.add('active');
            }
        }
    });

    // Marcar dropdown activo si contiene enlaces activos
    const dropdownToggles = document.querySelectorAll('.sidebar .dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        const dropdown = toggle.nextElementSibling;
        const dropdownLinks = dropdown.querySelectorAll('.dropdown-item');
        
        dropdownLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href) {
                // Obtener la última parte de la URL actual y del href
                const currentPage = currentPath.split('/').filter(p => p).pop();
                const linkPage = href.split('/').filter(p => p).pop();
                
                // Comparación exacta (no parcial)
                if (currentPage === linkPage) {
                    toggle.classList.add('active');
                }
            }
        });
    });
});

// Función para manejar cambios de tamaño de ventana
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // En desktop, asegurar que el sidebar esté visible normalmente
    if (window.innerWidth > 768) {
        sidebar.classList.remove('show');
        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('show');
        }
    }
    // En móvil, el sidebar siempre está visible arriba, no necesita toggle
});

