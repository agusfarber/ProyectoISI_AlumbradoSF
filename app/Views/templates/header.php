<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proyecto 1 - Codeigniter 4</title> 

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <?php if (isset($cssPageFile)) { ?>
    <link rel="stylesheet" href="<?= base_url($cssPageFile); ?>">
    <?php } ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.1/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if (isset($title) && (strtolower($title) === 'analisis' || strtolower($title) === 'análisis')): ?>
    <!-- ApexCharts CSS -->
    <style>
        .apexcharts-canvas { width: 100% !important; }
        .apexcharts-legend { padding: 10px 0 !important; }
        .chart-container canvas { max-width: 100% !important; }
    </style>
    <?php endif; ?>


    <link rel="stylesheet" href="<?php echo base_url('/static/css/darkSweetAlert.css'); ?>">

    <link rel="stylesheet" href="<?= base_url('/static/css/menu.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('/static/css/global.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('/static/css/tables.css'); ?>">

    <?php if (isset($cssPageFile)) { ?>
    <link rel="stylesheet" href="<?= base_url($cssPageFile); ?>">
    <?php } ?>
    
    <?php
    // Pasar el rol del usuario al JavaScript
    $session = \Config\Services::session();
    $userRole = $session->get('role');
    $userId = $session->get('user_id');
    $userName = $session->get('user_name');
    ?>
    <?php if (in_array((string) $userRole, ['1', '2', '3'], true)): ?>
    <link rel="stylesheet" href="<?= base_url('/static/css/lumen.css'); ?>">
    <?php endif; ?>
    <script>
        // Variables globales de sesión
        window.USER_ROLE = '<?= $userRole ?>';
        window.USER_ID = <?= $userId !== null && $userId !== '' ? (int) $userId : 'null' ?>;
        window.USER_NAME = <?= json_encode((string) ($userName ?? ''), JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>

<body data-user-role="<?= $userRole ?>">
  <div class="contenido" id="app">