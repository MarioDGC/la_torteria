<!-- /app/views/layouts/dashboard.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo BASE_URL; ?>">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    
    <?php include APP_PATH . '/views/partials/favicons.php'; ?>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos globales del dashboard -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/main.css">
    
    <!-- Estilos específicos opcionales (solo si es necesario) -->
    <?php if (isset($customCSS)): ?>
        <?php foreach ($customCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/<?php echo $css; ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php include APP_PATH . '/views/partials/sidebar.php'; ?>

    <main class="main-content">
        <?php include APP_PATH . '/views/partials/navbar.php'; ?>

        <div class="dashboard-container">
            <?php echo $content; ?>
        </div>
    </main>

    <!-- Bootstrap Bundle JS (incluye Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js solo si se necesita -->
<?php if (isset($useCharts) && $useCharts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>

<!-- Toast System (global) - SIEMPRE primero -->
<script src="<?php echo ASSETS_URL; ?>/js/toast.js"></script>

<!-- Dashboard Base JS - SOLO si no está skipDashboardJS -->
<?php if (!isset($skipDashboardJS) || $skipDashboardJS === false): ?>
    <script src="<?php echo ASSETS_URL; ?>/js/dashboard.js"></script>
<?php endif; ?>

<!-- ✅ Scripts específicos opcionales -->
<?php if (isset($customJS)): ?>
    <?php foreach ($customJS as $js): ?>
        <script src="<?php echo ASSETS_URL; ?>/js/<?php echo $js; ?>.js"></script>
    <?php endforeach; ?>
<?php endif; ?>

    
</body>
</html>