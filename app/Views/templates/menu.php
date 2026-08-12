<?php if ($required) {
    $esLayoutSimple = ($userRole == '1' || $userRole == '3');

    $rolLabel = 'Usuario';
    switch ((string) $userRole) {
        case '1':
            $rolLabel = 'Administrador';
            break;
        case '2':
            $rolLabel = 'Supervisor';
            break;
        case '3':
            $rolLabel = 'Operador';
            break;
    }

    if ($userRole == '1') {
        $homeUrl = base_url('/usuarios');
        $homeTitle = 'Usuarios';
        $homeIcon = 'bi-people';
    } else {
        $homeUrl = base_url('/tareas');
        $homeTitle = 'Tareas';
        $homeIcon = 'bi-clipboard-check';
    }
?>

<?php if ($esLayoutSimple): ?>

<!-- Layout compacto: admin / operario (una sola sección) -->
<header class="app-topbar">
  <a class="app-topbar__brand" href="<?= $homeUrl ?>" title="<?= esc($homeTitle) ?>">
    <span class="app-topbar__icon"><i class="bi <?= esc($homeIcon) ?>"></i></span>
    <span class="app-topbar__text">
      <strong><?= esc($homeTitle) ?></strong>
      <small><?= esc($rolLabel) ?></small>
    </span>
  </a>

  <div class="user-menu user-menu--topbar" id="userMenuTopbar">
    <button type="button" class="user-info user-menu__toggle" aria-haspopup="true" aria-expanded="false" title="Mi cuenta">
      <div class="user-avatar">
        <?php if (!empty($userFoto)): ?>
          <img src="<?= base_url('static/uploads/perfiles/' . $userFoto) ?>" alt="Foto de perfil" class="user-avatar-img">
        <?php else: ?>
          <i class="bi bi-person"></i>
        <?php endif; ?>
      </div>
      <div class="user-details">
        <p class="username"><?= esc($username) ?></p>
        <p class="user-role"><?= esc($rolLabel) ?></p>
      </div>
      <i class="bi bi-chevron-down user-info__chevron"></i>
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
</header>

<div class="main-content main-content--topbar">
  <div class="content-wrapper">

<?php else: ?>

<!-- Botón toggle para móvil -->
<button class="mobile-toggle" id="sidebarToggle">
  <i class="bi bi-list"></i>
</button>

<!-- Overlay para móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar lateral izquierdo (supervisor) -->
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
          <p class="username"><?= esc($username) ?></p>
          <p class="user-role"><?= esc($rolLabel) ?></p>
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
    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/cuadrillas"); ?>" title="Cuadrillas">
        <i class="bi bi-people-fill"></i>
        <span>Cuadrillas</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/reclamos"); ?>" title="Reclamos">
        <i class="bi bi-exclamation-triangle"></i>
        <span>Reclamos</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/cierre_reclamos"); ?>" title="Cierre">
        <i class="bi bi-lock-fill"></i>
        <span>Cierre</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/materiales"); ?>" title="Materiales">
        <i class="bi bi-box-seam"></i>
        <span>Materiales</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/mapa_google"); ?>" title="Mapa">
        <i class="bi bi-geo-alt"></i>
        <span>Mapa</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/rutas"); ?>" title="Rutas">
        <i class="bi bi-map"></i>
        <span>Rutas</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/analisis"); ?>" title="Análisis">
        <i class="bi bi-graph-up"></i>
        <span>Análisis</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link" href="<?= base_url("/notas"); ?>" title="Notas">
        <i class="bi bi-journal-text"></i>
        <span>Notas</span>
      </a>
    </div>
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

<?php endif; ?>

<?php } else { ?>
<!-- Para páginas sin menú (como login), solo el contenedor principal -->
<div class="content-wrapper">
<?php } ?>
