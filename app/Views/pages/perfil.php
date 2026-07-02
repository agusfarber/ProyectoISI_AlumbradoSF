<?php
$perfil = $perfil ?? null;

$nombre = $perfil['nombre'] ?? ($username ?? 'Usuario');
$email = $perfil['email'] ?? null;
$legajo = $perfil['legajo'] ?? null;
$foto = $perfil['foto_perfil'] ?? ($userFoto ?? null);
$idRol = (string) ($perfil['idRol'] ?? ($userRole ?? ''));
$userId = $perfil['id'] ?? null;

$roles = ['1' => 'Administrador', '2' => 'Supervisor', '3' => 'Operador'];
$rolLabel = $roles[$idRol] ?? 'Usuario';

// Solo el administrador tiene correo electrónico.
// Supervisores y operarios se identifican únicamente con su legajo.
$esAdmin = ($idRol === '1');

// Iniciales para el avatar (cuando no hay foto)
$partes = preg_split('/\s+/', trim($nombre));
$ini = '';
if (!empty($partes[0])) {
    $ini .= mb_substr($partes[0], 0, 1);
}
if (count($partes) > 1) {
    $ini .= mb_substr($partes[count($partes) - 1], 0, 1);
}
$iniciales = mb_strtoupper($ini ?: '?');

// Color determinístico según el nombre
$paleta = ['#3A3972', '#6E6D99', '#2D6A6A', '#7A5C9E', '#A65A7A', '#4C6EA8', '#9E7B3A'];
$hash = 0;
$len = mb_strlen($nombre);
for ($i = 0; $i < $len; $i++) {
    $hash = mb_ord(mb_substr($nombre, $i, 1)) + (($hash << 5) - $hash);
}
$colorAvatar = $paleta[abs($hash) % count($paleta)];
?>

<div class="perfil-page" data-user-id="<?= esc($userId, 'attr') ?>">

    <div class="perfil-page-title">Mi Perfil</div>

    <div class="perfil-card">
        <!-- Cabecera con avatar -->
        <div class="perfil-card__header">
            <div class="perfil-avatar">
                <?php if (!empty($foto)): ?>
                    <img id="perfilAvatarImg" src="<?= base_url('static/uploads/perfiles/' . $foto) ?>" alt="Foto de perfil">
                <?php else: ?>
                    <span id="perfilAvatarIniciales" class="perfil-avatar__iniciales" style="background-color: <?= esc($colorAvatar, 'attr') ?>;"><?= esc($iniciales) ?></span>
                <?php endif; ?>

                <?php if (!empty($userId)): ?>
                    <button type="button" class="perfil-avatar__editar" id="btnCambiarFoto" title="Cambiar foto de perfil">
                        <i class="bi bi-camera-fill"></i>
                    </button>
                    <input type="file" id="inputPerfilFoto" accept="image/png, image/jpeg, image/webp" hidden>
                <?php endif; ?>
            </div>

            <h2 class="perfil-nombre"><?= esc($nombre) ?></h2>
            <span class="perfil-rol-badge">
                <i class="bi bi-shield-check"></i> <?= esc($rolLabel) ?>
            </span>
        </div>

        <!-- Detalles -->
        <div class="perfil-card__body">
            <div class="perfil-dato">
                <span class="perfil-dato__icon"><i class="bi bi-person"></i></span>
                <div class="perfil-dato__info">
                    <span class="perfil-dato__label">Nombre completo</span>
                    <span class="perfil-dato__valor"><?= esc($nombre) ?></span>
                </div>
            </div>

<?php if ($esAdmin): ?>
            <div class="perfil-dato">
                <span class="perfil-dato__icon"><i class="bi bi-envelope"></i></span>
                <div class="perfil-dato__info">
                    <span class="perfil-dato__label">Correo electrónico</span>
                    <span class="perfil-dato__valor"><?= !empty($email) ? esc($email) : '<span class="perfil-dato__vacio">No registrado</span>' ?></span>
                </div>
            </div>
<?php else: ?>
            <div class="perfil-dato">
                <span class="perfil-dato__icon"><i class="bi bi-card-text"></i></span>
                <div class="perfil-dato__info">
                    <span class="perfil-dato__label">Legajo</span>
                    <span class="perfil-dato__valor"><?= !empty($legajo) ? esc($legajo) : '<span class="perfil-dato__vacio">No registrado</span>' ?></span>
                </div>
            </div>
<?php endif; ?>

            <div class="perfil-dato">
                <span class="perfil-dato__icon"><i class="bi bi-shield-check"></i></span>
                <div class="perfil-dato__info">
                    <span class="perfil-dato__label">Rol</span>
                    <span class="perfil-dato__valor"><?= esc($rolLabel) ?></span>
                </div>
            </div>
        </div>
    </div>

</div>
