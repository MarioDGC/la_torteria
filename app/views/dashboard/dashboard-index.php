<?php
/**
 * Vista: Dashboard principal
 * Requiere autenticación
 */

// Verificar sesión (esto lo haremos con middleware después)
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'Usuario';
$userRole = $_SESSION['user_role'] ?? 'Invitado';
?>

<div class="page-header">
    <div>
        <h1>Dashboard</h1>
        <p class="text-muted">Resumen general del restaurante</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline">
            <i class="fas fa-download"></i>
            Exportar
        </button>
        <button class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Nueva Venta
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-primary">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Ventas Hoy</span>
            <h3 class="stat-value" id="ventasHoy">$0.00</h3>
            <span class="stat-change positive">
                <i class="fas fa-arrow-up"></i> 12.5%
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Órdenes Hoy</span>
            <h3 class="stat-value" id="ordenesHoy">0</h3>
            <span class="stat-change positive">
                <i class="fas fa-arrow-up"></i> 8.2%
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-warning">
            <i class="fas fa-table"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Mesas Ocupadas</span>
            <h3 class="stat-value" id="mesasOcupadas">0/12</h3>
            <span class="stat-change neutral">
                <i class="fas fa-minus"></i> 0%
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-info">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Tiempo Promedio</span>
            <h3 class="stat-value" id="tiempoPromedio">0 min</h3>
            <span class="stat-change negative">
                <i class="fas fa-arrow-down"></i> 3.1%
            </span>
        </div>
    </div>
</div>

<!-- Charts and Tables Row -->
<div class="content-grid">
    <!-- Sales Chart -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-chart-line"></i> Ventas Semanales</h5>
            <select class="form-select-sm">
                <option>Última semana</option>
                <option>Último mes</option>
                <option>Último año</option>
            </select>
        </div>
        <div class="card-body">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Top Dishes -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-fire"></i> Platillos Más Vendidos</h5>
        </div>
        <div class="card-body">
            <div class="dish-list" id="topDishes">
                <!-- Se llenará con JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-receipt"></i> Órdenes Recientes</h5>
        <a href="#" class="btn-link">Ver todas</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Mesa</th>
                        <th>Mesero</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Hora</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="recentOrders">
                    <!-- Se llenará con JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>/js/toast.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/dashboard.js"></script>
<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>