<div class="login-container">
    <div class="login-header">
        <div class="header-title">
            <div class="icon"><i class="fa-solid fa-utensils"></i></div>
            <h3 class="fw-bolder"><?php echo APP_NAME; ?></h3>
        </div>
        <p>Sistema de Punto de Venta</p>
    </div>

    <form method="POST" action="<?php echo BASE_URL; ?>/auth/authenticate" novalidate>
        <div class="form-group">
            <label class="form-label" for="username">
                <i class="fas fa-user"></i> Usuario
            </label>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input
                    type="text"
                    class="form-control form-control-sm"
                    id="username"
                    name="username"
                    placeholder="Ingresa tu usuario"
                    value="<?php echo htmlspecialchars($lastUsername); ?>"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">
                <i class="fas fa-lock"></i> Contraseña
            </label>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input
                    type="password"
                    class="form-control form-control-sm"
                    id="password"
                    name="password"
                    placeholder="Ingresa tu contraseña"
                    required
                    autocomplete="current-password"
                >
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </button>
    </form>

    <!-- Errores de validación (vienen desde el controlador) -->
    <?php if (!empty($validationErrors)): ?>
        <div class="alert alert-danger fade show mt-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Corrige los siguientes errores:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($validationErrors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Error de credenciales incorrectas -->
    <?php if ($credentialsError): ?>
        <div class="alert alert-danger fade show mt-3" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            Usuario o contraseña incorrectos
        </div>
    <?php endif; ?>

    <?php if ($timeout): ?>
        <div class="alert alert-warning fade show mt-3" role="alert">
            <i class="fas fa-clock"></i>
            Tu sesión ha expirado por inactividad
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['account_locked_until']) && $_SESSION['account_locked_until'] > time()): ?>
        <div class="alert alert-danger fade show mt-3" role="alert">
            <i class="fas fa-ban"></i>
            <strong>Cuenta bloqueada temporalmente</strong><br>
            Demasiados intentos fallidos. Intenta nuevamente en <?php echo ceil(($_SESSION['account_locked_until'] - time()) / 60); ?> minuto(s).
        </div>
    <?php endif; ?>

    <?php if (APP_ENV === 'development'): ?>
        <div class="dev-credentials">
            <p><strong>Modo Desarrollo</strong></p>
            <p>Usuario: <code>admin</code> | Contraseña: <code>admin123</code></p>
        </div>
    <?php endif; ?>
</div>
