<?php
/**
 * Vista: Crear Orden (Mesero)
 * Permite seleccionar platillos y enviar a cocina
 */
// Extraer datos pasados por el controlador
$account = $account ?? null;
$categories = $categories ?? [];
$sideDishes = $sideDishes ?? [];
$currentOrders = $currentOrders ?? [];

if (!$account) {
    echo '<div class="alert alert-danger">Error: Datos de cuenta no disponibles</div>';
    return;
}
?>

<!-- ✅ CRÍTICO: Data hidden para JavaScript -->
<div id="tableData" 
     data-table-id="<?php echo htmlspecialchars($account['table_id']); ?>"
     data-table-number="<?php echo htmlspecialchars($account['table_number']); ?>"
     data-account-id="<?php echo htmlspecialchars($account['id']); ?>"
     style="display: none;">
</div>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1>
            <i class="fas fa-receipt"></i> 
            Nueva Orden - Mesa <?php echo htmlspecialchars($account['table_number']); ?>
        </h1>
        <p class="text-muted">
            Mesero: <?php echo htmlspecialchars($account['waiter_name']); ?>
        </p>
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
    <div class="row g-3" data-account-id="<?php echo $account['id']; ?>" data-user-id="<?php echo $_SESSION['user_id']; ?>">
        <!-- Panel Izquierdo: Menú de Platillos (8 columnas en desktop) -->
        <div class="col-12 col-lg-8">
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
                                        class="nav-link category-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
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
                                    
                                    <!-- ✅ Bootstrap Grid: 2 columnas en desktop, 1 en mobile -->
                                    <div class="dishes-grid">
                                        <div class="row g-3">
                                            <?php foreach ($category['dishes'] as $dish): ?>
                                                <div class="col-12 col-md-6">
                                                    <div class="dish-card" 
                                                        data-dish-id="<?php echo $dish['id']; ?>"
                                                        data-dish-name="<?php echo htmlspecialchars($dish['name']); ?>"
                                                        data-dish-price="<?php echo $dish['price']; ?>"
                                                        data-dish-description="<?php echo htmlspecialchars($dish['description'] ?? ''); ?>"
                                                        data-requires-side="<?php echo $dish['requires_side_dish']; ?>">
                                                        
                                                        <!-- ✅ Row 1: Nombre y Precio -->
                                                        <div class="row g-2 mb-2 align-items-center">
                                                            <div class="col">
                                                                <h6 class="dish-name"><?php echo htmlspecialchars($dish['name']); ?></h6>
                                                            </div>
                                                            <div class="col-auto">
                                                                <div class="dish-price">$<?php echo number_format($dish['price'], 0); ?></div>
                                                            </div>
                                                        </div>

                                                        <!-- ✅ Row 2: Botones -->
                                                        <div class="row g-2 align-items-center">
                                                            <div class="col-auto">
                                                                <button class="btn-dish-details" 
                                                                        title="Ver detalles del platillo">
                                                                    <i class="fas fa-info-circle"></i>
                                                                    <span>Detalles</span>
                                                                </button>
                                                            </div>
                                                            <div class="col offset-md-4 offset-3">
                                                                <button class="btn-add-dish" 
                                                                        title="Agregar al carrito">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Derecho: Carrito de Órdenes (4 columnas en desktop) -->
        <div class="col-12 col-lg-4">
            <div class="cart-panel">
                <div class="card cart-card">
                    <div class="card-header">
                        <h5><i class="fas fa-shopping-cart"></i> Carrito</h5>
                        <button class="btn btn-sm btn-outline-danger" id="btnClearCart">
                            <i class="fas fa-trash"></i> Vaciar
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Empty state -->
                        <div class="empty-cart" id="emptyCart">
                            <i class="fas fa-shopping-cart fa-3x"></i>
                            <p>El carrito está vacío</p>
                            <small class="text-muted">Agrega platillos del menú</small>
                        </div>

                        <!-- Cart items -->
                        <div class="cart-items" id="cartItems" style="display: none;">
                            <!-- Los items se agregan dinámicamente -->
                        </div>
                    </div>
                    
                    <!-- Cart summary -->
                    <div class="card-footer" id="cartSummary" style="display: none;">
                        <div class="cart-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <strong id="cartSubtotal">$0.00</strong>
                            </div>
                            <div class="summary-row">
                                <span>IVA (16%):</span>
                                <strong id="cartTax">$0.00</strong>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <strong id="cartTotal">$0.00</strong>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary w-100 mt-3" id="btnSubmitOrder">
                            <i class="fas fa-paper-plane"></i> Enviar Orden
                        </button>
                    </div>
                </div>

                <!-- Órdenes Actuales -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6><i class="fas fa-list"></i> Órdenes Actuales</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($currentOrders)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p class="mb-0">No hay órdenes previas</p>
                            </div>
                        <?php else: ?>
                            <div class="previous-orders">
                                <?php foreach ($currentOrders as $order): ?>
                                    <div class="order-item status-<?php echo $order['status']; ?>">
                                        <div class="order-info">
                                            <strong><?php echo htmlspecialchars($order['dish_name']); ?></strong>
                                            <?php if ($order['side_dish_name']): ?>
                                                <small>con <?php echo htmlspecialchars($order['side_dish_name']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <span class="order-quantity">x<?php echo $order['quantity']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="configDishId">
                
                <div class="mb-3">
                    <label class="form-label">Platillo</label>
                    <h6 id="configDishName">-</h6>
                    <p class="text-muted mb-0" id="configDishPrice">$0.00</p>
                </div>

                <div class="mb-3" id="sideDishGroup" style="display: none;">
                    <label class="form-label">Guisado <span class="text-danger">*</span></label>
                    <select class="form-select" id="configSideDish">
                        <option value="">-- Seleccionar guisado --</option>
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
                              placeholder="Ej: Sin cebolla, término 3/4, etc."
                              maxlength="200"></textarea>
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

<!-- Modal: Detalles del Platillo -->
<div class="modal fade" id="dishDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="detailDishName">Detalles del Platillo</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">X</button>
            </div>
            <div class="modal-body">
                <div class="dish-details-content">
                    <!-- Descripción -->
                    <div class="detail-section" id="detailDescriptionSection" style="display: none;">
                        <label class="detail-label">
                            <i class="fas fa-align-left"></i> Descripción:
                        </label>
                        <p class="detail-description" id="detailDishDescription"></p>
                    </div>
                    <!-- Requiere guisado -->
                    <div class="detail-alert" id="detailSideDishAlert" style="display: none;">
                        <i class="fas fa-utensils"></i>
                        <span>Este platillo requiere seleccionar un guisado</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">
                    Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btnAddFromDetails">
                    <i class="fas fa-plus"></i> Agregar al carrito
                </button>
            </div>
        </div>
    </div>
</div>