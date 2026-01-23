<?php
/**
 * Vista: Cocina (Chef)
 * Muestra comandas pendientes en tiempo real
 */
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fas fa-fire"></i> Cocina</h1>
        <p class="text-muted">Comandas en tiempo real</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline" id="refreshOrders">
            <i class="fas fa-sync-alt"></i> Actualizar
        </button>
        <span class="last-update" id="lastUpdate"></span>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Pendientes</span>
            <h3 class="stat-value" id="pendingCount">0</h3>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-info">
            <i class="fas fa-fire"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">En Preparación</span>
            <h3 class="stat-value" id="preparingCount">0</h3>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Listos</span>
            <h3 class="stat-value" id="readyCount">0</h3>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-primary">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Tiempo Promedio</span>
            <h3 class="stat-value" id="avgTime">0 min</h3>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <button class="filter-tab active" data-filter="all">
        <i class="fas fa-border-all"></i> Todas
    </button>
    <button class="filter-tab" data-filter="pending">
        <i class="fas fa-clock"></i> Pendientes
    </button>
    <button class="filter-tab" data-filter="preparing">
        <i class="fas fa-fire"></i> En Preparación
    </button>
</div>

<!-- Orders Grid -->
<div class="kitchen-orders-grid" id="ordersGrid">
    <!-- Las órdenes se cargarán dinámicamente -->
</div>

<!-- Empty State -->
<div class="empty-state" id="emptyState" style="display: none;">
    <i class="fas fa-check-circle fa-3x"></i>
    <h3>¡Todo listo!</h3>
    <p>No hay comandas pendientes en este momento</p>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Toast System -->
<script src="<?php echo ASSETS_URL; ?>/js/toast.js"></script>

<!-- Dashboard Base JS -->
<script src="<?php echo ASSETS_URL; ?>/js/dashboard.js"></script>

<!-- Orders Kitchen JS -->
<script>
    const APP_CONFIG = {
        baseUrl: '<?php echo BASE_URL; ?>',
        userId: <?php echo $_SESSION['user_id']; ?>
    };
</script>
<script src="<?php echo ASSETS_URL; ?>/js/orders-kitchen.js"></script>