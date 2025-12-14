<?php
/**
 * Vista: Detalle de Cuenta
 * Muestra todas las órdenes y permite gestionarlas
 */

// Agrupar órdenes por estado
$ordersByStatus = [
    'pending' => [],
    'preparing' => [],
    'ready' => [],
    'served' => []
];

foreach ($orders as $order) {
    if (isset($ordersByStatus[$order['status']])) {
        $ordersByStatus[$order['status']][] = $order;
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fas fa-receipt"></i> Cuenta - Mesa <?php echo htmlspecialchars($account['table_number']); ?></h1>
        <p class="text-muted">
            Mesero: <?php echo htmlspecialchars($account['waiter_name']); ?>
        </p>
    </div>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>/tables" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <a href="<?php echo BASE_URL; ?>/orders/create?account=<?php echo $account['id']; ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Agregar Platillos
        </a>
    </div>
</div>

<!-- Account Summary -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-info-circle"></i> Resumen de Cuenta</h5>
    </div>
    <div class="card-body">
        <div class="account-summary-grid">
            <div class="summary-item">
                <span class="summary-label">Total de Órdenes:</span>
                <strong><?php echo count($orders); ?></strong>
            </div>
            <div class="summary-item">
                <span class="summary-label">Subtotal:</span>
                <strong>$<?php echo number_format($account['subtotal'], 2); ?></strong>
            </div>
            <div class="summary-item">
                <span class="summary-label">IVA (16%):</span>
                <strong>$<?php echo number_format($account['tax'], 2); ?></strong>
            </div>
            <div class="summary-item total">
                <span class="summary-label">Total:</span>
                <strong>$<?php echo number_format($account['total'], 2); ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Orders by Status -->
<div class="orders-status-layout">
    
    <!-- Pendientes / En Preparación -->
    <div class="status-column">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h6><i class="fas fa-clock"></i> En Cocina</h6>
                <span class="badge bg-dark">
                    <?php echo count($ordersByStatus['pending']) + count($ordersByStatus['preparing']); ?>
                </span>
            </div>
            <div class="card-body">
                <?php 
                $inKitchen = array_merge($ordersByStatus['pending'], $ordersByStatus['preparing']);
                if (empty($inKitchen)): 
                ?>
                    <div class="empty-column">
                        <i class="fas fa-check-circle"></i>
                        <p>No hay órdenes en cocina</p>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($inKitchen as $order): ?>
                            <div class="order-card status-<?php echo $order['status']; ?>" 
                                 data-order-id="<?php echo $order['id']; ?>">
                                <div class="order-card-header">
                                    <strong><?php echo htmlspecialchars($order['dish_name']); ?></strong>
                                    <span class="order-quantity">x<?php echo $order['quantity']; ?></span>
                                </div>
                                
                                <?php if ($order['side_dish_name']): ?>
                                    <div class="order-detail">
                                        <i class="fas fa-utensils"></i>
                                        con <?php echo htmlspecialchars($order['side_dish_name']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($order['special_instructions']): ?>
                                    <div class="order-detail special">
                                        <i class="fas fa-comment"></i>
                                        <?php echo htmlspecialchars($order['special_instructions']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="order-status-badge">
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Preparando</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Listos para Servir -->
    <div class="status-column">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6><i class="fas fa-check-circle"></i> Listos para Servir</h6>
                <span class="badge bg-light text-dark">
                    <?php echo count($ordersByStatus['ready']); ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($ordersByStatus['ready'])): ?>
                    <div class="empty-column">
                        <i class="fas fa-clock"></i>
                        <p>Esperando órdenes listas</p>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($ordersByStatus['ready'] as $order): ?>
                            <div class="order-card status-ready highlight" 
                                 data-order-id="<?php echo $order['id']; ?>">
                                <div class="order-card-header">
                                    <strong><?php echo htmlspecialchars($order['dish_name']); ?></strong>
                                    <span class="order-quantity">x<?php echo $order['quantity']; ?></span>
                                </div>
                                
                                <?php if ($order['side_dish_name']): ?>
                                    <div class="order-detail">
                                        <i class="fas fa-utensils"></i>
                                        con <?php echo htmlspecialchars($order['side_dish_name']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <button class="btn btn-success btn-sm btn-block mt-2 btn-mark-served"
                                        data-order-id="<?php echo $order['id']; ?>">
                                    <i class="fas fa-check"></i> Marcar como Servido
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Servidos -->
    <div class="status-column">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h6><i class="fas fa-history"></i> Servidos</h6>
                <span class="badge bg-light text-dark">
                    <?php echo count($ordersByStatus['served']); ?>
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($ordersByStatus['served'])): ?>
                    <div class="empty-column">
                        <i class="fas fa-inbox"></i>
                        <p>No hay órdenes servidas aún</p>
                    </div>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($ordersByStatus['served'] as $order): ?>
                            <div class="order-card status-served" 
                                 data-order-id="<?php echo $order['id']; ?>">
                                <div class="order-card-header">
                                    <strong><?php echo htmlspecialchars($order['dish_name']); ?></strong>
                                    <span class="order-quantity">x<?php echo $order['quantity']; ?></span>
                                </div>
                                
                                <?php if ($order['side_dish_name']): ?>
                                    <div class="order-detail">
                                        <i class="fas fa-utensils"></i>
                                        con <?php echo htmlspecialchars($order['side_dish_name']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="order-price">
                                    $<?php echo number_format($order['subtotal'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Toast System -->
<script src="<?php echo ASSETS_URL; ?>/js/toast.js"></script>

<!-- Dashboard Base JS -->
<script src="<?php echo ASSETS_URL; ?>/js/dashboard.js"></script>

<!-- Account Detail JS -->
<script>
    const APP_CONFIG = {
        baseUrl: '<?php echo BASE_URL; ?>',
        accountId: <?php echo $account['id']; ?>
    };
</script>
<script src="<?php echo ASSETS_URL; ?>/js/account-detail.js"></script>