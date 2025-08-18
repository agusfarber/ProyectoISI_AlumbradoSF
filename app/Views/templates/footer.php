<!-- Cerramos app Vue js  -->
</div>

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
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfMK5sCP34GT1jFPSSTVORThl1Y6pHVow&loading=async&libraries=maps&v=beta" defer>
</script>

<!-- SweetAlert2 debe cargarse antes del archivo JavaScript específico de la página -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<?php if (isset($jsPageFile)) { ?>
  <script src="<?= base_url($jsPageFile); ?>"></script><?php } ?>
<script src="<?php echo base_url("/static/js/tools.js"); ?>"></script>






<!-- Mapbox -->
<link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
<!-- Utilidades -->

<?php
if (isset($_SERVER["PATH_INFO"])) {
  $current_page = $_SERVER["PATH_INFO"];
}
?>

<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

</body>

</html>