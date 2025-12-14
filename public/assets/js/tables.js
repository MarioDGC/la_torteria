/**
 * Tables Management JavaScript
 * Gestión de mesas con actualización en tiempo real
 *
 * Patrón: Module Pattern para encapsulación
 * @author Mario
 * @version 1.0.0
 */

(function () {
  "use strict";

  // ========================================
  // Variables Globales
  // ========================================
  const state = {
    currentFilter: "all",
    tables: [],
    refreshInterval: null,
    isRefreshing: false,
  };

  // Elementos DOM
  const elements = {
    tablesGrid: document.getElementById("tablesGrid"),
    filterTabs: document.querySelectorAll(".filter-tab"),
    refreshBtn: document.getElementById("refreshBtn"),
    // Stats
    availableCount: document.getElementById("availableCount"),
    occupiedCount: document.getElementById("occupiedCount"),
    reservedCount: document.getElementById("reservedCount"),
    maintenanceCount: document.getElementById("maintenanceCount"),
  };

  // ========================================
  // Inicialización
  // ========================================
  document.addEventListener("DOMContentLoaded", () => {
    initEventListeners();
    loadWaiters();
    startAutoRefresh();
    console.log("Tables module initialized");
  });

  document.addEventListener("DOMContentLoaded", () => {
    // ✅ Verificar que estamos en la vista correcta
    if (!elements.tablesGrid) {
      console.log("Tables module: Not in tables view");
      return;
    }

    // Initialize modals only if elements exist
    const assignModalEl = document.getElementById("assignWaiterModal");
    const statusModalEl = document.getElementById("changeStatusModal");

    if (assignModalEl) {
      elements.assignModal = new bootstrap.Modal(assignModalEl);
    }

    if (statusModalEl) {
      elements.statusModal = new bootstrap.Modal(statusModalEl);
    }

    initEventListeners();
    startAutoRefresh();
    console.log("Tables module initialized");
  });

  /**
   * Configura todos los event listeners
   */
  function initEventListeners() {
    // Filter tabs
    elements.filterTabs.forEach((tab) => {
      tab.addEventListener("click", handleFilterChange);
    });

    // Refresh button
    if (elements.refreshBtn) {
      // ✅ Verificar que existe
      elements.refreshBtn.addEventListener("click", () => {
        refreshTablesStatus();
      });
    }

    // Search
    const searchInput = document.getElementById("searchTable");
    if (searchInput) {
      // ✅ Verificar que existe
      searchInput.addEventListener("input", handleSearch);
    }

    // Table actions (Event Delegation)
    if (elements.tablesGrid) {
      // ✅ Verificar que existe
      elements.tablesGrid.addEventListener("click", handleTableAction);
    }

    // Modal actions
    document
      .getElementById("confirmAssign")
      ?.addEventListener("click", handleAssignWaiter);
    document
      .getElementById("confirmStatusChange")
      ?.addEventListener("click", handleStatusChange);

    // Cargar datos iniciales del HTML
    loadInitialTableData();
  }

  /**
   * Carga datos iniciales desde el HTML
   */
  function loadInitialTableData() {
    const tableCards = document.querySelectorAll(".table-card");

    state.tables = Array.from(tableCards).map((card) => {
      const tableId = card.dataset.tableId;
      const status = card.dataset.status;
      const tableNumber = card.querySelector(
        ".table-number strong"
      ).textContent;
      const capacityText = card.querySelector(
        ".info-item:first-child span"
      ).textContent;
      const capacity = parseInt(capacityText);

      // Extraer ubicación si existe
      const locationElement = card.querySelector(
        ".info-item:nth-child(2) span"
      );
      const location = locationElement ? locationElement.textContent : null;

      return {
        id: parseInt(tableId),
        table_number: tableNumber,
        status: status,
        capacity: capacity,
        location: location,
      };
    });

    console.log("Datos iniciales cargados:", state.tables.length, "mesas");
  }

  // ========================================
  // Filtrado de Mesas
  // ========================================

  /**
   * Maneja el cambio de filtro
   * @param {Event} e - Evento de click
   */
  function handleFilterChange(e) {
    const filter = e.currentTarget.dataset.filter;

    // Update active tab
    elements.filterTabs.forEach((tab) => tab.classList.remove("active"));
    e.currentTarget.classList.add("active");

    // Apply filter
    state.currentFilter = filter;
    applyFilter();
  }

  /**
   * Aplica el filtro actual a las tarjetas de mesas
   */
  function applyFilter() {
    const cards = document.querySelectorAll(".table-card");

    cards.forEach((card) => {
      const status = card.dataset.status;

      if (state.currentFilter === "all" || status === state.currentFilter) {
        card.style.display = "block";
        // Fade in animation
        setTimeout(() => {
          card.style.opacity = "1";
          card.style.transform = "translateY(0)";
        }, 10);
      } else {
        card.style.opacity = "0";
        card.style.transform = "translateY(-10px)";
        setTimeout(() => {
          card.style.display = "none";
        }, 300);
      }
    });
  }

  /**
   * Maneja la búsqueda de mesas
   * @param {Event} e - Evento de input
   */
  function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    const cards = document.querySelectorAll(".table-card");

    cards.forEach((card) => {
      const tableNumber = card
        .querySelector(".table-number strong")
        .textContent.toLowerCase();
      const location =
        card
          .querySelector(".info-item:nth-child(2) span")
          ?.textContent.toLowerCase() || "";

      const matches =
        tableNumber.includes(searchTerm) || location.includes(searchTerm);

      card.style.display = matches ? "block" : "none";
    });
  }

  // ========================================
  // Acciones de Mesa
  // ========================================

  /**
   * Maneja los clicks en acciones de mesa (Event Delegation Pattern)
   * @param {Event} e - Evento de click
   */
  function handleTableAction(e) {
    const target = e.target.closest("button");
    if (!target) return;

    const tableId = target.dataset.tableId;

    if (target.classList.contains("btn-assign")) {
      openAssignModal(tableId);
    } else if (target.classList.contains("btn-view")) {
      const accountId = target.dataset.accountId;
      viewAccount(accountId);
    } else if (target.classList.contains("btn-close")) {
      closeTable(tableId);
    } else if (target.classList.contains("btn-status")) {
      openStatusModal(tableId);
    }
  }

  /**
   * Abre modal para asignar mesero
   * @param {number} tableId - ID de la mesa
   */
  function openAssignModal(tableId) {
    document.getElementById("assignTableId").value = tableId;
    elements.assignModal.show();
  }

  /**
   * Abre modal para cambiar estado
   * @param {number} tableId - ID de la mesa
   */
  function openStatusModal(tableId) {
    // Buscar datos de la mesa en el state
    const table = state.tables.find((t) => t.id == tableId);

    if (!table) {
      console.error("Mesa no encontrada:", tableId);
      return;
    }

    // Establecer ID de mesa en campo hidden
    document.getElementById("statusTableId").value = tableId;

    // Actualizar info visual de la mesa
    document.getElementById("modalTableNumber").textContent =
      table.table_number;

    // Actualizar badge de estado actual
    const currentStatusBadge = document.getElementById("modalCurrentStatus");
    const statusLabels = {
      available: "Disponible",
      occupied: "Ocupada",
      reserved: "Reservada",
      maintenance: "Mantenimiento",
    };

    currentStatusBadge.textContent = statusLabels[table.status] || table.status;
    currentStatusBadge.className = `badge status-badge status-${table.status}`;

    // Reset del select
    document.getElementById("newStatus").value = "";

    // Deshabilitar la opción del estado actual en el select
    const selectOptions = document.querySelectorAll("#newStatus option");
    selectOptions.forEach((option) => {
      option.disabled = option.value === table.status;
    });

    // Mostrar modal
    elements.statusModal.show();
  }

  /**
   * Ver cuenta de una mesa
   * @param {number} accountId - ID de la cuenta
   */
  function viewAccount(accountId) {
    window.location.href = `${APP_CONFIG.baseUrl}/orders/view/${accountId}`;
  }

  /**
   * Cierra una mesa (confirma primero)
   * @param {number} tableId - ID de la mesa
   */
  function closeTable(tableId) {
    if (!confirm("¿Estás seguro de cerrar esta cuenta?")) {
      return;
    }

    // Aquí iría la lógica para cerrar la cuenta
    // Por ahora solo redirige a la vista de cierre
    window.location.href = `${APP_CONFIG.baseUrl}/orders/close/${tableId}`;
  }

  // ========================================
  // Asignar Mesero
  // ========================================

  /**
   * Carga lista de meseros disponibles
   */
  async function loadWaiters() {
    try {
      // Waiters are already loaded server-side in the select
      // This function is kept for future AJAX implementations
      console.log("Waiters pre-loaded from server");
    } catch (error) {
      console.error("Error loading waiters:", error);
    }
  }

  /**
   * Llena el select de meseros
   * @param {Array} waiters - Lista de meseros
   */
  function populateWaitersSelect(waiters) {
    const select = document.getElementById("waiterId");
    select.innerHTML = '<option value="">-- Seleccionar --</option>';

    waiters.forEach((waiter) => {
      const option = document.createElement("option");
      option.value = waiter.id;
      option.textContent = waiter.full_name;

      // Pre-seleccionar si es el usuario actual
      if (waiter.id === APP_CONFIG.userId) {
        option.selected = true;
      }

      select.appendChild(option);
    });
  }

  /**
   * Asigna mesero a mesa
   */
  async function handleAssignWaiter() {
    const tableId = document.getElementById("assignTableId").value;
    const waiterId = document.getElementById("waiterId").value;

    if (!waiterId) {
      alert("Por favor selecciona un mesero");
      return;
    }

    try {
      const formData = new FormData();
      formData.append("table_id", tableId);
      formData.append("waiter_id", waiterId);

      const response = await fetch(
        `${APP_CONFIG.baseUrl}/tables/assignWaiter`,
        {
          method: "POST",
          body: formData,
        }
      );

      const data = await response.json();

      if (data.success) {
        showNotification("success", data.message);
        elements.assignModal.hide();
        refreshTablesStatus();
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error("Error assigning waiter:", error);
      showNotification("error", error.message || "Error al asignar mesero");
    }
  }

  // ========================================
  // Cambiar Estado
  // ========================================

  /**
   * Cambia el estado de una mesa
   */
  async function handleStatusChange() {
    const tableId = document.getElementById("statusTableId").value;
    const newStatus = document.getElementById("newStatus").value;

    if (!newStatus) {
      alert("Por favor selecciona un estado");
      return;
    }

    try {
      const formData = new FormData();
      formData.append("table_id", tableId);
      formData.append("new_status", newStatus);

      const response = await fetch(
        `${APP_CONFIG.baseUrl}/tables/changeStatus`,
        {
          method: "POST",
          body: formData,
        }
      );

      const data = await response.json();

      if (data.success) {
        showNotification("success", data.message);
        elements.statusModal.hide();
        refreshTablesStatus();
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error("Error changing status:", error);
      showNotification("error", error.message || "Error al cambiar estado");
    }
  }

  // ========================================
  // Actualización en Tiempo Real
  // ========================================

  /**
   * Inicia actualización automática cada 30 segundos
   * Patrón: Polling para actualización en tiempo real
   */
  function startAutoRefresh() {
    state.refreshInterval = setInterval(() => {
      refreshTablesStatus(true); // silent refresh
    }, 30000); // 30 segundos
  }

  /**
   * Detiene la actualización automática
   */
  function stopAutoRefresh() {
    if (state.refreshInterval) {
      clearInterval(state.refreshInterval);
      state.refreshInterval = null;
    }
  }

  /**
   * Actualiza el estado de las mesas desde el servidor
   * @param {boolean} silent - Si es true, no muestra notificación
   */
  async function refreshTablesStatus(silent = false) {
    if (state.isRefreshing) return;

    state.isRefreshing = true;

    if (!silent) {
      elements.refreshBtn.innerHTML =
        '<i class="fas fa-sync-alt fa-spin"></i> Actualizando...';
      elements.refreshBtn.disabled = true;
    }

    try {
      const response = await fetch(
        `${APP_CONFIG.baseUrl}/tables/getTablesStatus`
      );

      if (!response.ok) {
        throw new Error("Error al actualizar estado");
      }

      const data = await response.json();

      if (data.success) {
        updateTablesUI(data.tables);
        updateStatsUI(data.stats);

        if (!silent) {
          showNotification("success", "Datos actualizados");
        }
      }
    } catch (error) {
      console.error("Error refreshing tables:", error);
      if (!silent) {
        showNotification("error", "Error al actualizar datos");
      }
    } finally {
      state.isRefreshing = false;

      if (!silent) {
        elements.refreshBtn.innerHTML =
          '<i class="fas fa-sync-alt"></i> Actualizar';
        elements.refreshBtn.disabled = false;
      }
    }
  }

  /**
   * Actualiza la UI de las tarjetas de mesas
   * @param {Array} tables - Datos actualizados de mesas
   */
  function updateTablesUI(tables) {
    state.tables = tables;

    tables.forEach((table) => {
      const card = document.querySelector(`[data-table-id="${table.id}"]`);
      if (!card) return;

      // Actualizar estado
      card.dataset.status = table.status;

      // Actualizar badge de estado
      const statusBadge = card.querySelector(".status-badge");
      statusBadge.className = `status-badge status-${table.status}`;

      const statusLabels = {
        available: "Disponible",
        occupied: "Ocupada",
        reserved: "Reservada",
        maintenance: "Mantenimiento",
      };
      statusBadge.textContent = statusLabels[table.status];

      // Actualizar info si está ocupada
      if (table.status === "occupied" && table.waiter_name) {
        updateOccupiedTableInfo(card, table);
      } else {
        // Limpiar info si ya no está ocupada
        const tableInfo = card.querySelector(".table-info");
        tableInfo.innerHTML = `
                    <div class="info-item">
                        <i class="fas fa-chair"></i>
                        <span>${table.capacity} personas</span>
                    </div>
                    ${
                      table.location
                        ? `
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${table.location}</span>
                        </div>
                    `
                        : ""
                    }
                `;
      }

      // Actualizar botones de acción
      updateTableActions(card, table);
    });

    // Re-aplicar filtro actual
    applyFilter();
  }

  /**
   * Actualiza información de mesa ocupada
   * @param {HTMLElement} card - Card de la mesa
   * @param {Object} table - Datos de la mesa
   */
  function updateOccupiedTableInfo(card, table) {
    const tableInfo = card.querySelector(".table-info");
    tableInfo.innerHTML = `
            <div class="info-item">
                <i class="fas fa-chair"></i>
                <span>${table.capacity} personas</span>
            </div>
            ${
              table.location
                ? `
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>${table.location}</span>
                </div>
            `
                : ""
            }
            <div class="info-item">
                <i class="fas fa-user"></i>
                <span>${table.waiter_name}</span>
            </div>
            <div class="info-item">
                <i class="fas fa-receipt"></i>
                <span>${table.order_count || 0} órdenes</span>
            </div>
            <div class="info-item total">
                <i class="fas fa-dollar-sign"></i>
                <strong>$${parseFloat(table.current_total || 0).toFixed(
                  2
                )}</strong>
            </div>
        `;
  }

  /**
   * Actualiza los botones de acción según el estado
   * @param {HTMLElement} card - Card de la mesa
   * @param {Object} table - Datos de la mesa
   */
  function updateTableActions(card, table) {
    const actionsDiv = card.querySelector(".table-actions");

    let actionsHTML = "";

    if (table.status === "available") {
      actionsHTML = `
                <button class="btn-action btn-assign" data-table-id="${table.id}">
                    <i class="fas fa-user-plus"></i> Asignar
                </button>
            `;
    } else if (table.status === "occupied") {
      actionsHTML = `
                <button class="btn-action btn-view" 
                        data-table-id="${table.id}"
                        data-account-id="${table.account_id}">
                    <i class="fas fa-eye"></i> Ver Cuenta
                </button>
                <button class="btn-action btn-close" data-table-id="${table.id}">
                    <i class="fas fa-times-circle"></i> Cerrar
                </button>
            `;
    }

    actionsHTML += `
            <button class="btn-action btn-status" data-table-id="${table.id}">
                <i class="fas fa-exchange-alt"></i>
            </button>
        `;

    actionsDiv.innerHTML = actionsHTML;
  }

  /**
   * Actualiza las estadísticas en el UI
   * @param {Object} stats - Estadísticas actualizadas
   */
  function updateStatsUI(stats) {
    elements.availableCount.textContent = stats.available || 0;
    elements.occupiedCount.textContent = stats.occupied || 0;
    elements.reservedCount.textContent = stats.reserved || 0;
    elements.maintenanceCount.textContent = stats.maintenance || 0;
  }

  // ========================================
  // Utilidades
  // ========================================

  /**
   * Muestra notificación toast
   * @param {string} type - Tipo: 'success', 'error', 'warning', 'info'
   * @param {string} message - Mensaje a mostrar
   */
  function showNotification(type, message) {
    // Por ahora usamos alert, después implementar toast library
    console.log(`[${type.toUpperCase()}] ${message}`);

    // Alternativa simple con Bootstrap toast
    const toastHTML = `
            <div class="toast align-items-center text-white bg-${
              type === "error" ? "danger" : type
            } border-0" 
                 role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                            data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

    document.body.insertAdjacentHTML("beforeend", toastHTML);
    const toastElement = document.body.lastElementChild;
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();

    // Remover del DOM después de ocultar
    toastElement.addEventListener("hidden.bs.toast", () => {
      toastElement.remove();
    });
  }

  // Cleanup al salir de la página
  window.addEventListener("beforeunload", () => {
    stopAutoRefresh();
  });
})();
