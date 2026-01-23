
<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><i class="fas fa-table"></i> Gestión de Mesas</h1>
        <p class="text-muted">Administra el estado y asignación de mesas</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-outline" id="refreshBtn">
            <i class="fas fa-sync-alt"></i> Actualizar
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Disponibles</span>
            <h3 class="stat-value" id="availableCount"><?php echo $stats['available'] ?? 0; ?></h3>
            <span class="stat-change neutral">de <?php echo $stats['total'] ?? 0; ?> mesas</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-primary">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Ocupadas</span>
            <h3 class="stat-value" id="occupiedCount"><?php echo $stats['occupied'] ?? 0; ?></h3>
            <span class="stat-change neutral">
                <?php 
                    $total = $stats['total'] ?? 0;
                    $occupied = $stats['occupied'] ?? 0;
                    echo $total > 0 ? round(($occupied / $total) * 100) : 0; 
                ?>% ocupación
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Reservadas</span>
            <h3 class="stat-value" id="reservedCount"><?php echo $stats['reserved'] ?? 0; ?></h3>
            <span class="stat-change neutral">pendientes</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-info">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
            <span class="stat-label">Mantenimiento</span>
            <h3 class="stat-value" id="maintenanceCount"><?php echo $stats['maintenance'] ?? 0; ?></h3>
            <span class="stat-change neutral">fuera de servicio</span>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <button class="filter-tab active" data-filter="all">
        <i class="fas fa-border-all"></i> Todas
    </button>
    <button class="filter-tab" data-filter="available">
        <i class="fas fa-check-circle"></i> Disponibles
    </button>
    <button class="filter-tab" data-filter="occupied">
        <i class="fas fa-users"></i> Ocupadas
    </button>
    <button class="filter-tab" data-filter="reserved">
        <i class="fas fa-clock"></i> Reservadas
    </button>
</div>

<!-- Tables Grid -->
<div class="tables-grid" id="tablesGrid">
    <?php foreach ($tables as $table): ?>
        <div class="table-card" 
                data-table-id="<?php echo $table['id']; ?>"
                data-status="<?php echo $table['status']; ?>">
            
            <!-- Table Number Badge -->
            <div class="table-number">
                <span>Mesa</span>
                <strong><?php echo htmlspecialchars($table['table_number']); ?></strong>
            </div>

            <!-- Status Badge -->
            <div class="status-badge status-<?php echo $table['status']; ?>">
                <?php
                $statusLabels = [
                    'available' => 'Disponible',
                    'occupied' => 'Ocupada',
                    'reserved' => 'Reservada',
                    'maintenance' => 'Mantenimiento'
                ];
                echo $statusLabels[$table['status']] ?? $table['status'];
                ?>
            </div>

            <!-- Table Info -->
            <div class="table-info">
                <div class="info-item">
                    <i class="fas fa-chair"></i>
                    <span><?php echo $table['capacity']; ?> personas</span>
                </div>
                
                <?php if ($table['location']): ?>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($table['location']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($table['status'] === 'occupied' && $table['waiter_name']): ?>
                    <div class="info-item">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($table['waiter_name']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-receipt"></i>
                        <span><?php echo $table['order_count']; ?> órdenes</span>
                    </div>

                    <div class="info-item total">
                        <i class="fas fa-dollar-sign"></i>
                        <strong>$<?php echo number_format($table['current_total'], 2); ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="table-actions">
                <?php if ($table['status'] === 'available'): ?>
                    <button class="btn-action btn-assign" 
                            data-table-id="<?php echo $table['id']; ?>"
                            title="Asignar mesa">
                        <i class="fas fa-user-plus"></i> Asignar
                    </button>
                <?php elseif ($table['status'] === 'occupied'): ?>
                    <button class="btn-action btn-view" 
                            data-table-id="<?php echo $table['id']; ?>"
                            data-account-id="<?php echo $table['account_id']; ?>"
                            title="Ver cuenta">
                        <i class="fas fa-eye"></i> Ver Cuenta
                    </button>
                    <button class="btn-action btn-close" 
                            data-table-id="<?php echo $table['id']; ?>"
                            title="Cerrar cuenta">
                        <i class="fas fa-times-circle"></i> Cerrar
                    </button>
                <?php endif; ?>

                <button class="btn-action btn-status" 
                        data-table-id="<?php echo $table['id']; ?>"
                        title="Cambiar estado">
                    <i class="fas fa-exchange-alt"></i>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($tables)): ?>
    <div class="empty-state">
        <i class="fas fa-table fa-3x"></i>
        <h3>No hay mesas registradas</h3>
        <p>Agrega mesas para comenzar a gestionarlas</p>
    </div>
<?php endif; ?>

<!-- Modal: Asignar Mesero -->
<div class="modal fade" id="assignWaiterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i> Asignar Mesero
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignWaiterForm">
                    <input type="hidden" id="assignTableId" name="table_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Mesero</label>
                        <select class="form-select" id="waiterId" name="waiter_id" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($waiters ?? [] as $waiter): ?>
                                <option value="<?php echo $waiter['id']; ?>">
                                    <?php echo htmlspecialchars($waiter['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmAssign">
                    <i class="fas fa-check"></i> Asignar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Cambiar Estado -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt"></i> Cambiar Estado de Mesa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Info de la mesa -->
                <div>
                    <i class="fa-solid fa-grip-vertical"></i><strong> Mesa <span id="modalTableNumber">-</span></strong> | Estado actual: <span class="badge" id="modalCurrentStatus">-</span>
                </div>

                <form id="changeStatusForm">
                    <input type="hidden" id="statusTableId" name="table_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nuevo Estado</label>
                        <select class="form-select" id="newStatus" name="new_status" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="available">Disponible</option>
                            <option value="occupied">Ocupada</option>
                            <option value="reserved">Reservada</option>
                            <option value="maintenance">Mantenimiento</option>
                        </select>
                        <div class="form-text">
                            Selecciona el nuevo estado para la mesa
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmStatusChange">
                    <i class="fas fa-check"></i> Cambiar Estado
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tables Specific JS -->
<script>
    const APP_CONFIG = {
        baseUrl: '<?php echo BASE_URL; ?>',
        assetsUrl: '<?php echo ASSETS_URL; ?>',
        userId: <?php echo $_SESSION['user_id'] ?? 0; ?>,
        userRole: '<?php echo $_SESSION['role'] ?? 'guest'; ?>',
        userName: '<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario', ENT_QUOTES); ?>'
    };
</script>