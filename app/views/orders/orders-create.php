<?php
/**
 * Vista: Crear Orden (Mesero)
 * Permite seleccionar platillos y enviar a cocina
 */
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fas fa-utensils"></i> Tomar Orden</h1>
        <p class="text-muted">
            Mesa <?php echo htmlspecialchars($account['table_number']); ?> 
            - <?php echo htmlspecialchars($account['waiter_name']); ?>
        </p>
    </div>
    <div class="header-actions">
        <a href="<?php echo BASE_URL; ?>/tables" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon bg-primary">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Órdenes Actuales</span>
            <h3 class="stat-value"><?php echo count($currentOrders); ?></h3>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Total Cuenta</span>
            <h3 class="stat-value">$<?php echo number_format($account['total'], 2); ?></h3>
        </div>
    </div>
</div>

<!-- Layout: Menú y Carrito -->
<div class="orders-layout">
    
    <!-- Panel Izquierdo: Menú de Platillos -->
    <div class="menu-panel">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-book-open"></i> Menú</h5>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchDish" placeholder="Buscar platillo...">
                </div>
            </div>
            <div class="card-body">
                <!-- Tabs de Categorías -->
                <ul class="nav nav-tabs category-tabs" role="tablist">
                    <?php foreach ($categories as $index => $category): ?>
                        <li class="nav-item" role="presentation">
                            <button 
                                class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                id="cat-<?php echo $category['id']; ?>-tab"
                                data-bs-toggle="tab" 
                                data-bs-target="#cat-<?php echo $category['id']; ?>"
                                type="button">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Contenido de Categorías -->
                <div class="tab-content mt-3">
                    <?php foreach ($categories as $index => $category): ?>
                        <div 
                            class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                            id="cat-<?php echo $category['id']; ?>">
                            
                            <div class="dishes-grid">
                                <?php foreach ($category['dishes'] as $dish): ?>
                                    <div class="dish-card" 
                                         data-dish-id="<?php echo $dish['id']; ?>"
                                         data-dish-name="<?php echo htmlspecialchars($dish['name']); ?>"
                                         data-dish-price="<?php echo $dish['price']; ?>"
                                         data-requires-side="<?php echo $dish['requires_side_dish'] ? '1' : '0'; ?>">
                                        
                                        <?php if ($dish['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($dish['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($dish['name']); ?>">
                                        <?php else: ?>
                                            <div class="dish-placeholder">
                                                <i class="fas fa-utensils"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div class="dish-info">
                                            <h6><?php echo htmlspecialchars($dish['name']); ?></h6>
                                            <?php if ($dish['description']): ?>
                                                <p><?php echo htmlspecialchars($dish['description']); ?></p>
                                            <?php endif; ?>
                                            <div class="dish-footer">
                                                <span class="dish-price">$<?php echo number_format($dish['price'], 2); ?></span>
                                                <button class="btn-add-dish" data-dish-id="<?php echo $dish['id']; ?>">
                                                    <i class="fas fa-plus"></i> Agregar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Derecho: Carrito de Órdenes -->
    <div class="cart-panel">
        <div class="card cart-card">
            <div class="card-header">
                <h5><i class="fas fa-shopping-cart"></i> Orden Actual</h5>
                <button class="btn-clear-cart" id="clearCart" style="display: none;">
                    <i class="fas fa-trash"></i> Limpiar
                </button>
            </div>
            <div class="card-body">
                <div id="cartItems" class="cart-items">
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Agrega platillos para comenzar</p>
                    </div>
                </div>

                <div class="cart-summary" id="cartSummary" style="display: none;">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <strong id="cartSubtotal">$0.00</strong>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <strong id="cartTotal">$0.00</strong>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary btn-block" id="sendToKitchen" disabled>
                    <i class="fas fa-paper-plane"></i> Enviar a Cocina
                </button>
            </div>
        </div>

        <!-- Órdenes Previas -->
        <?php if (!empty($currentOrders)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="fas fa-history"></i> Órdenes Anteriores</h6>
                </div>
                <div class="card-body">
                    <div class="previous-orders">
                        <?php foreach ($currentOrders as $order): ?>
                            <div class="order-item status-<?php echo $order['status']; ?>">
                                <div class="order-info">
                                    <strong><?php echo htmlspecialchars($order['dish_name']); ?></strong>
                                    <?php if ($order['side_dish_name']): ?>
                                        <small>con <?php echo htmlspecialchars($order['side_dish_name']); ?></small>
                                    <?php endif; ?>
                                    <span class="order-quantity">x<?php echo $order['quantity']; ?></span>
                                </div>
                                <span class="order-status badge-status <?php echo $order['status']; ?>">
                                    <?php 
                                    $statusText = [
                                        'pending' => 'Pendiente',
                                        'preparing' => 'Preparando',
                                        'ready' => 'Listo',
                                        'served' => 'Servido'
                                    ];
                                    echo $statusText[$order['status']] ?? $order['status'];
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Configurar Platillo -->
<div class="modal fade" id="dishConfigModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog"></i> Configurar Platillo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="configDishId">
                
                <div class="mb-3">
                    <label class="form-label">Platillo</label>
                    <h6 id="configDishName"></h6>
                    <p class="text-muted" id="configDishPrice"></p>
                </div>

                <div class="mb-3" id="sideDishGroup" style="display: none;">
                    <label class="form-label">Guisado <span class="text-danger">*</span></label>
                    <select class="form-select" id="configSideDish" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($sideDishes as $side): ?>
                            <option value="<?php echo $side['id']; ?>">
                                <?php echo htmlspecialchars($side['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Los desayunos requieren seleccionar un guisado</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cantidad</label>
                    <div class="quantity-control">
                        <button type="button" class="btn-quantity" id="decreaseQty">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="form-control" id="configQuantity" value="1" min="1" max="20">
                        <button type="button" class="btn-quantity" id="increaseQty">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Instrucciones Especiales (Opcional)</label>
                    <textarea class="form-control" id="configInstructions" rows="3" 
                              placeholder="Ej: Sin cebolla, término 3/4, etc."></textarea>
                    <small class="text-muted">Máximo 200 caracteres</small>
                </div>

                <div class="modal-summary">
                    <span>Subtotal:</span>
                    <strong id="modalSubtotal">$0.00</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmAddToCart">
                    <i class="fas fa-check"></i> Agregar al Carrito
                </button>
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

<!-- Orders Waiter JS -->
<script>
    // Configuración global
    const APP_CONFIG = {
        baseUrl: '<?php echo BASE_URL; ?>',
        accountId: <?php echo $account['id']; ?>,
        userId: <?php echo $_SESSION['user_id']; ?>
    };
</script>
<script src="<?php echo ASSETS_URL; ?>/js/orders-waiter.js"></script>