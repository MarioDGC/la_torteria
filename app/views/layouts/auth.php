<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Autenticación'; ?> - <?php echo APP_NAME; ?></title>

    <!-- Favicons -->
    <?php include APP_PATH . '/views/partials/favicons.php'; ?>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Variables base (opcional) -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/base/variables.css">

    <!-- CSS personalizado -->
    <?php if (isset($customCSS)): ?>
        <?php foreach ($customCSS as $style): ?>
            <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/<?php echo $style; ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Contenido dinámico -->
    <?php echo $content; ?>

    <!-- Scripts específicos de la vista -->
    <?php if (isset($customJS)): ?>
        <?php foreach ($customJS as $script): ?>
            <script src="<?php echo ASSETS_URL; ?>/js/<?php echo $script; ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>