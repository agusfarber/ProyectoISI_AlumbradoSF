<?php if($required) { ?>

<!-- Menu Superior -->
<div class="header-fac d-flex justify-content-between align-items-center px-3">
  <div class="d-flex align-items-center">

    <div class="me-4">
      <a class="nav-link" href="<?= base_url("/dashboard"); ?>" title="Dashboard">
        <p class="description m-0">Dashboard</p>
      </a>
    </div>

    <?php if ($userRole == '1'): ?>
    <div class="me-4">
      <a class="nav-link" href="<?= base_url("/usuarios"); ?>" title="Usuarios">
        <p class="description m-0">Usuarios</p>
      </a>
    </div>
    <?php endif; ?>

    <!--
    <div class="me-4">
      <a class="nav-link" href="<?= base_url("/reclamos"); ?>" title="Reclamos">
        <p class="description m-0">Reclamos</p>
      </a>
    </div>

    <div class="me-4">
      <a class="nav-link" href="<?= base_url("/materiales"); ?>" title="Materiales">
        <p class="description m-0">Materiales</p>
      </a>
    </div>
    
    <div class="me-4">
      <a class="nav-link" href="<?= base_url("/mapa"); ?>" title="Mapa">
        <p class="description m-0">Mapa</p>
      </a>
    </div-->
    

    <!--
    <?php if ($userRole == '1'): ?>
    <div class="me-4 text-danger">
      <a class="nav-link" href="<?= base_url("/pasajero"); ?>" title="Pasajeros">
        <p class="description m-0">Pasajeros</p>
      </a>
    </div>
    <?php endif; ?>

    <div class="dropdown me-4 text-danger">
      <button class="btn dropdown-toggle text-danger" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        Conductores
      </button>
      <ul class="dropdown-menu">
        <li>
          <a class="dropdown-item" href="<?= base_url("/conductor"); ?>" title="Listado de conductores">
            Listado de conductores
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?= base_url("/solicitudesConductores"); ?>" title="">
            Solicitudes de conductores
          </a>
        </li>
      </ul>
    </div>

    <?php if ($userRole == '1'): ?>
    <div class="dropdown me-4 text-danger">
      <button class="btn dropdown-toggle text-danger" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        Organizaciones
      </button>
      <ul class="dropdown-menu">
        <li>
          <a class="dropdown-item" href="<?= base_url("/organizacion"); ?>" title="Listado de organizaciones">
            Listado de organizaciones
          </a>
        </li>
        <li>
          <a class="dropdown-item" href="<?= base_url("/supervisor"); ?>" title="">
            Listado de supervisores
          </a>
        </li>
      </ul>
    </div>
    <?php endif; ?>

    <div class="dropdown me-4 text-danger">
      <button class="btn dropdown-toggle text-danger" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        Certificaciones
      </button>
      <ul class="dropdown-menu">
        <li>
          <a class="dropdown-item" href="<?= base_url("/listadoCertificacionesConductores"); ?>" title="Listado de vehículos">
            Certificaciones de conductores
          </a>
        </li>
      </ul>
    </div>

    <div class="me-4 text-danger">
      <a class="nav-link" href="<?= base_url("/"); ?>" title="Informe de ganancias">
        <p class="description m-0">Informe de ganancias</p>
      </a>
    </div>

    <div class="me-4 text-danger">
      <a class="nav-link" href="<?= base_url("/"); ?>" title="Reseñas y valoraciones">
        <p class="description m-0">Reseñas y valoraciones</p>
      </a>
    </div>
    -->

    

    
  </div>

  <div class="d-flex align-items-center">
    <div class="dropdown-center">
      <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <svg class="mx-1" width="24" height="24" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2c5.52 0 10 4.48 10 10s-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2ZM6.023 15.416C7.491 17.606 9.695 19 12.16 19c2.464 0 4.669-1.393 6.136-3.584A8.968 8.968 0 0 0 12.16 13a8.968 8.968 0 0 0-6.137 2.416ZM12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z">
          </path>
        </svg><?php echo $username; ?>
      </button>
      <ul class="dropdown-menu">
        <li>
          <form action="<?= base_url('auth/logout') ?>" method="POST" style="margin: 0;">
            <button type="submit" class="dropdown-item">Cerrar sesión</button>
          </form>
        </li>
      </ul>
    </div>
  </div>
</div>


<?php } ?>