<?php
$sessionLumen = \Config\Services::session();
$rolLumen = (string) $sessionLumen->get('role');
$lumenActivo = in_array($rolLumen, ['1', '2', '3'], true);
?>

    </div><!-- content-wrapper -->
<?php if ($lumenActivo): ?>
  </div><!-- main-content -->
<?php endif; ?>
</div><!-- #app: fuera de Vue para que Lúmen no se borre al renderizar -->

<?php if ($lumenActivo): ?>
<div id="lumenRoot" class="lumen-root">
    <div id="lumenPanel" class="lumen-panel" role="dialog" aria-label="Lúmen, asistente de la plataforma" aria-hidden="true">
        <div class="lumen-panel__header">
            <button type="button" class="lumen-panel__iconbtn" id="lumenBack" hidden aria-label="Volver">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span class="lumen-panel__avatar" aria-hidden="true">
                <svg class="lumen-face lumen-face--lit" viewBox="18 6 36 66">
                    <defs>
                        <radialGradient id="lumenOnH" cx="38%" cy="30%" r="72%">
                            <stop offset="0%" stop-color="#ffe9a0"/>
                            <stop offset="55%" stop-color="#f5c84c"/>
                            <stop offset="100%" stop-color="#e3922a"/>
                        </radialGradient>
                    </defs>
                    <path fill="url(#lumenOnH)" d="M36 8c-12.5 0-22.5 10-22.5 22.4 0 8.7 5.2 16.3 12.7 20V54h19.6v-3.6c7.5-3.7 12.7-11.3 12.7-20C58.5 18 48.5 8 36 8z"/>
                    <g class="lumen-filament" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path class="lumen-filament-post" d="M31 50.6L29.5 35.2" stroke="#c4b8a0" stroke-width="1.15"/>
                        <path class="lumen-filament-post" d="M41 50.6L42.5 35.2" stroke="#c4b8a0" stroke-width="1.15"/>
                        <path class="lumen-filament-coil" d="M29.5 35.2c1.8-8.6 5.4-8.6 6.5-2.1 1.2 6.4 5 6.4 6.5 0 1.2-6.5 4.7-6.5 6.5 2.1" stroke="#e8a030" stroke-width="1.45"/>
                    </g>
                    <ellipse cx="26.5" cy="19.5" rx="6.2" ry="4.2" fill="#fff" opacity=".42"/>
                    <rect x="27.4" y="53.7" width="17.2" height="3.1" rx=".7" fill="#e8e4dc"/>
                    <rect x="28.1" y="56.6" width="15.8" height="3.1" rx=".45" fill="#d0d5de"/>
                    <rect x="28.8" y="59.5" width="14.4" height="2.9" rx=".4" fill="#b7bec9"/>
                    <rect x="29.6" y="62.2" width="12.8" height="2.7" rx=".35" fill="#9aa3b0"/>
                    <rect x="31.4" y="64.7" width="9.2" height="4" rx="1.7" fill="#6b7380"/>
                </svg>
            </span>
            <div class="lumen-panel__title">
                <strong>Lúmen</strong>
                <small id="lumenStatus">Asistente de la plataforma</small>
            </div>
            <button type="button" class="lumen-panel__iconbtn" id="lumenClose" aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="lumen-body" id="lumenBody"></div>
        <div class="lumen-composer">
            <label class="lumen-composer__field">
                <input id="lumenSearch" type="search" placeholder="Escribí un mensaje o pregunta…" autocomplete="off">
            </label>
        </div>
    </div>
    <button type="button" class="lumen-fab" id="lumenFab" aria-label="Abrir Lúmen" aria-expanded="false" title="Lúmen">
        <svg class="lumen-fab__shape" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2z"/>
        </svg>
        <svg class="lumen-face lumen-fab__bulb" viewBox="13 7 46 62" aria-hidden="true">
            <defs>
                <radialGradient id="lumenOffF" cx="38%" cy="30%" r="72%">
                    <stop offset="0%" stop-color="#d5dae2"/>
                    <stop offset="100%" stop-color="#8b93a3"/>
                </radialGradient>
                <radialGradient id="lumenOnF" cx="38%" cy="30%" r="72%">
                    <stop offset="0%" stop-color="#ffe9a0"/>
                    <stop offset="55%" stop-color="#f5c84c"/>
                    <stop offset="100%" stop-color="#e3922a"/>
                </radialGradient>
            </defs>
            <g fill="none" stroke="#1a1a1a" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                <path d="M24.5 45.5c-7.5-1.5-13-9-10.5-18"/>
                <path d="M13.2 26.2c-2.2-.8-4.2 1.2-2.8 3.2"/>
                <path d="M14.2 25.4c1.8-1.2 3.6.6 2.4 2.6"/>
                <path d="M47.5 46.5c7 2.5 10.5 11 7.5 17.5"/>
                <path d="M55.2 64.8c2.2 1.2 4.2-.2 3.6-2.4"/>
            </g>
            <path class="lumen-glass-off" fill="url(#lumenOffF)" d="M36 8c-12.5 0-22.5 10-22.5 22.4 0 8.7 5.2 16.3 12.7 20V54h19.6v-3.6c7.5-3.7 12.7-11.3 12.7-20C58.5 18 48.5 8 36 8z"/>
            <path class="lumen-glass-on" fill="url(#lumenOnF)" d="M36 8c-12.5 0-22.5 10-22.5 22.4 0 8.7 5.2 16.3 12.7 20V54h19.6v-3.6c7.5-3.7 12.7-11.3 12.7-20C58.5 18 48.5 8 36 8z"/>
            <g class="lumen-filament" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path class="lumen-filament-post" d="M31 50.6L29.5 35.2"/>
                <path class="lumen-filament-post" d="M41 50.6L42.5 35.2"/>
                <path class="lumen-filament-coil" d="M29.5 35.2c1.8-8.6 5.4-8.6 6.5-2.1 1.2 6.4 5 6.4 6.5 0 1.2-6.5 4.7-6.5 6.5 2.1"/>
            </g>
            <ellipse class="lumen-shine" cx="26.5" cy="19.5" rx="6.2" ry="4.2" fill="#fff"/>
            <rect x="27.4" y="53.7" width="17.2" height="3.1" rx=".7" fill="#e8e4dc"/>
            <rect x="28.1" y="56.6" width="15.8" height="3.1" rx=".45" fill="#d0d5de"/>
            <rect x="28.8" y="59.5" width="14.4" height="2.9" rx=".4" fill="#b7bec9"/>
            <rect x="29.6" y="62.2" width="12.8" height="2.7" rx=".35" fill="#9aa3b0"/>
            <rect x="31.4" y="64.7" width="9.2" height="4" rx="1.7" fill="#6b7380"/>
        </svg>
        <i class="bi bi-chevron-down lumen-fab__chevron" aria-hidden="true"></i>
    </button>
    <div id="lumenVisor" class="lumen-visor" aria-hidden="true">
        <div class="lumen-visor__marco">
            <button type="button" class="lumen-visor__cerrar" id="lumenVisorCerrar" aria-label="Cerrar imagen">
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="lumen-visor__cuerpo" id="lumenVisorCuerpo"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>var BASE_URL = "<?= base_url(); ?>/"</script>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<!-- jQuery (Debe cargarse antes de DataTables) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/2.2.1/js/dataTables.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<!-- JSZip y PDFMake (para exportar a Excel/PDF) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>


<!-- Utilidades -->
<script src="<?php echo base_url("/static/js/vue.global.js"); ?>"></script>
<script src="<?php echo base_url("/static/js/axios.min.js"); ?>"></script>
<script src="<?php echo base_url("/static/js/menu.js"); ?>"></script>
<script src="<?php echo base_url("/static/js/funcionesMaps.js"); ?>"></script>
<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAOCwr8_hWX4aBE2JTHxREP7gUrYLadCgg&loading=async&libraries=maps,places&v=beta" defer>
</script>

<!-- Mapbox -->
<link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>

<!-- SweetAlert2 debe cargarse antes del archivo JavaScript específico de la página -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<?php
$esPaginaMapaReclamos = !empty($jsPageFile)
    && preg_match('#/mapa_(google|mapbox)\.js$#', (string) $jsPageFile);
$esPaginaMapaPrioridad = !empty($jsPageFile)
    && preg_match('#/(mapa_(google|mapbox)|rutas|tareas)\.js$#', (string) $jsPageFile);
if ($esPaginaMapaPrioridad):
?>
<script src="<?php echo base_url('/static/js/mapa_prioridad.js'); ?>"></script>
<script src="<?php echo base_url('/static/js/obra_cronometro_util.js'); ?>"></script>
<?php endif; ?>
<?php if ($esPaginaMapaReclamos): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<?php endif; ?>

<?php if (isset($title) && (strtolower($title) === 'analisis' || strtolower($title) === 'análisis')): ?>
<!-- ApexCharts para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
<!-- Chart.js para gráfico de tiempo promedio -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php endif; ?>

<?php if (isset($jsPageFile)) { ?>
  <script src="<?= base_url($jsPageFile); ?>"></script><?php } ?>
<script src="<?php echo base_url("/static/js/tools.js"); ?>"></script>
<!-- Utilidades -->

<?php
if (isset($_SERVER["PATH_INFO"])) {
  $current_page = $_SERVER["PATH_INFO"];
}
?>

<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

<?php if ($lumenActivo): ?>
<script src="<?php echo base_url('/static/js/lumen.js'); ?>"></script>
<?php endif; ?>

</body>

</html>