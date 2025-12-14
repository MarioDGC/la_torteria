<?php
/**
 * Error 500 - Error interno del servidor
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Error del servidor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            color: white;
            padding: 3rem;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            line-height: 1;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        .error-description {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        .btn-home {
            background: white;
            color: #fa709a;
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            color: #fa709a;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">
            <i class="fas fa-exclamation-triangle"></i> 500
        </div>
        <div class="error-message">Error del servidor</div>
        <div class="error-description">
            Algo salió mal en el servidor. Estamos trabajando para solucionarlo.
        </div>
        <a href="<?php echo BASE_URL; ?>" class="btn-home">
            <i class="fas fa-home"></i> Volver al inicio
        </a>
    </div>
</body>
</html>