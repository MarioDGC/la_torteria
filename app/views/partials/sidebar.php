<!-- /app/views/partials/sidebar.php -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><?php echo APP_NAME; ?></h4>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>/dashboard" 
        class="nav-link <?php echo ($currentView ?? 'dashboard') === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/tables" 
        class="nav-link <?php echo ($currentView ?? '') === 'tables' ? 'active' : ''; ?>">
            <i class="fa-solid fa-grip-vertical"></i>
            <span>Mesas</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/orders/kitchen" 
        class="nav-link <?php echo ($currentView ?? '') === 'orders' ? 'active' : ''; ?>">
            <i class="fas fa-receipt"></i>
            <span>Comandas</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/menu" 
        class="nav-link <?php echo ($currentView ?? '') === 'menu' ? 'active' : ''; ?>">
            <i class="fas fa-utensils"></i>
            <span>Menú</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/inventory" 
        class="nav-link <?php echo ($currentView ?? '') === 'inventory' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Inventario</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/cashier" 
        class="nav-link <?php echo ($currentView ?? '') === 'cashier' ? 'active' : ''; ?>">
            <i class="fas fa-cash-register"></i>
            <span>Caja</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/reports" 
        class="nav-link <?php echo ($currentView ?? '') === 'reports' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Reportes</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/users" 
        class="nav-link <?php echo ($currentView ?? '') === 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Usuarios</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="#" class="nav-link">
            <i class="fas fa-cog"></i>
            <span>Configuración</span>
        </a>
        <a href="<?php echo BASE_URL; ?>/auth/logout" class="nav-link" id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>