<?php if($required) { ?>

<!-- Botón toggle para móvil -->
<button class="mobile-toggle" id="sidebarToggle">
  <i class="bi bi-list"></i>
</button>

<!-- Overlay para móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar lateral izquierdo -->
<div class="sidebar" id="sidebar">
  <!-- Header del sidebar: cuenta del usuario (escritorio) -->
  <div class="sidebar-header">
    <div class="user-menu user-menu--desktop" id="userMenu">
      <button type="button" class="user-info user-menu__toggle" id="userMenuToggle" aria-haspopup="true" aria-expanded="false" title="Mi cuenta">
        <div class="user-avatar">
          <?php if (!empty($userFoto)): ?>
            <img src="<?= base_url('static/uploads/perfiles/' . $userFoto) ?>" alt="Foto de perfil" class="user-avatar-img">
          <?php else: ?>
            <i class="bi bi-person"></i>
          <?php endif; ?>
        </div>
        <div class="user-details">
          <p class="username"><?php echo $username; ?></p>
          <p class="user-role">
            <?php 
            switch($userRole) {
              case '1': echo 'Administrador'; break;
              case '2': echo 'Supervisor'; break;
              case '3': echo 'Operador'; break;
              default: echo 'Usuario'; break;
            }
            ?>
          </p>
        </div>
        <i class="bi bi-chevron-down user-info__chevron"></i>
      </button>

      <div class="user-menu__dropdown" id="userMenuDropdown">
        <a class="user-menu__item" href="<?= base_url('/perfil') ?>">
          <i class="bi bi-person"></i> Perfil
        </a>
        <form action="<?= base_url('auth/logout') ?>" method="POST" style="margin: 0;">
          <button type="submit" class="user-menu__item user-menu__item--logout">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Navegación del sidebar -->
  <nav class="sidebar-nav">
    

    <?php if ($userRole == '1'): ?>
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/usuarios"); ?>" title="Usuarios">
        <i class="bi bi-people"></i>
        <span>Usuarios</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if ($userRole == '1' || $userRole == '2'): ?>
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/cuadrillas"); ?>" title="Cuadrillas">
        <i class="bi bi-people-fill"></i>
        <span>Cuadrillas</span>
      </a>
    </div>
    <?php endif; ?>
    
    <?php if ($userRole == '1' || $userRole == '2'): ?>
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/reclamos"); ?>" title="Reclamos">
        <i class="bi bi-exclamation-triangle"></i>
        <span>Reclamos</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if ($userRole == '1' || $userRole == '2'): ?>
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/cierre_reclamos"); ?>" title="Cierre">
        <i class="bi bi-lock-fill"></i>
        <span>Cierre</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if ($userRole == '1' || $userRole == '2' || $userRole == '3'): ?>
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/materiales"); ?>" title="Materiales">
        <i class="bi bi-box-seam"></i>
        <span>Materiales</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if ($userRole == '1' || $userRole == '2' || $userRole == '3'): ?>
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/mapa_google"); ?>" title="Mapa">
        <i class="bi bi-geo-alt"></i>
        <span>Mapa</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if ($userRole == '1' || $userRole == '2'): ?>
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/rutas"); ?>" title="Rutas">
        <i class="bi bi-map"></i>
        <span>Rutas</span>
      </a>
    </div>
    <?php endif; ?>
    
    <?php if ($userRole == '2'): ?>
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/analisis"); ?>" title="Análisis">
        <i class="bi bi-graph-up"></i>
        <span>Análisis</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if ($userRole == '1' ||$userRole == '3'): ?>
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/tareas"); ?>" title="Tareas">
        <i class="bi bi-clipboard-check"></i>
        <span>Tareas</span>
      </a>
    </div>
    <?php endif; ?>

    <!-- Menús comentados para futuras funcionalidades -->
    <!--
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/mapa"); ?>" title="Mapa">
        <i class="bi bi-geo-alt"></i>
        <span>Mapa</span>
      </a>
    </div>
    -->

    <!-- Dropdowns comentados para futuras funcionalidades -->
    <!--
    <?php if ($userRole == '1'): ?>
    <div class="sidebar-dropdown">
      <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span><i class="bi bi-people-fill"></i> Usuarios</span>
        <i class="bi bi-chevron-down"></i>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?= base_url("/pasajero"); ?>">Pasajeros</a></li>
        <li><a class="dropdown-item" href="<?= base_url("/conductor"); ?>">Conductores</a></li>
        <li><a class="dropdown-item" href="<?= base_url("/supervisor"); ?>">Supervisores</a></li>
      </ul>
    </div>
    <?php endif; ?>

    <div class="sidebar-dropdown">
      <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span><i class="bi bi-award"></i> Certificaciones</span>
        <i class="bi bi-chevron-down"></i>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?= base_url("/listadoCertificacionesConductores"); ?>">Certificaciones de conductores</a></li>
      </ul>
    </div>

    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/"); ?>" title="Informe de ganancias">
        <i class="bi bi-graph-up"></i>
        <span>Informe de ganancias</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/"); ?>" title="Reseñas y valoraciones">
        <i class="bi bi-star"></i>
        <span>Reseñas y valoraciones</span>
      </a>
    </div>
    -->
  </nav>

  <!-- Menú de cuenta para móvil (fuera del área de scroll) -->
  <div class="sidebar-footer-mobile">
    <div class="user-menu user-menu--mobile" id="userMenuMobile">
      <button type="button" class="user-menu__toggle user-avatar-btn" aria-haspopup="true" aria-expanded="false" title="Mi cuenta">
        <?php if (!empty($userFoto)): ?>
          <img src="<?= base_url('static/uploads/perfiles/' . $userFoto) ?>" alt="Foto de perfil" class="user-avatar-img">
        <?php else: ?>
          <i class="bi bi-person"></i>
        <?php endif; ?>
      </button>

      <div class="user-menu__dropdown">
        <a class="user-menu__item" href="<?= base_url('/perfil') ?>">
          <i class="bi bi-person"></i> Perfil
        </a>
        <form action="<?= base_url('auth/logout') ?>" method="POST" style="margin: 0;">
          <button type="submit" class="user-menu__item user-menu__item--logout">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
          </button>
        </form>
      </div>
    </div>
  </div>

</div>

<!-- Contenedor principal del contenido -->
<div class="main-content">
  <div class="content-wrapper">
    <!-- El contenido de las páginas se insertará aquí -->

<?php } else { ?>
<!-- Para páginas sin menú (como login), solo el contenedor principal -->
<div class="content-wrapper">
<?php } ?>