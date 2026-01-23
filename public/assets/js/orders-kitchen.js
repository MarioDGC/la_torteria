/**
 * Orders Kitchen JavaScript
 * Gestión de comandas para cocina
 *
 * Patrón: Module Pattern con Polling
 * @author Mario
 * @version 1.0.0
 */

(function () {
  "use strict";

  // ========================================
  // Estado de la Aplicación
  // ========================================
  const state = {
    orders: [],
    currentFilter: "all",
    pollingInterval: null,
    isRefreshing: false,
    lastUpdate: null,
  };

  // ========================================
  // Configuración
  // ========================================
  const config = {
    pollingDelay: 15000, // 15 segundos
    urgentThreshold: 10, // minutos
    warningThreshold: 5, // minutos
  };

  // ========================================
  // Elementos DOM
  // ========================================
  const elements = {
    ordersGrid: document.getElementById("ordersGrid"),
    emptyState: document.getElementById("emptyState"),
    refreshBtn: document.getElementById("refreshOrders"),
    lastUpdate: document.getElementById("lastUpdate"),
    filterTabs: document.querySelectorAll(".filter-tab"),

    // Stats
    pendingCount: document.getElementById("pendingCount"),
    preparingCount: document.getElementById("preparingCount"),
    readyCount: document.getElementById("readyCount"),
    avgTime: document.getElementById("avgTime"),
  };

  // ========================================
  // Inicialización
  // ========================================
  document.addEventListener("DOMContentLoaded", () => {
    initEventListeners();
    loadOrders();
    startPolling();
    console.log("Orders Kitchen Module Initialized");
  });

  /**
   * Inicializa event listeners
   */
  function initEventListeners() {
    // Filtros
    elements.filterTabs.forEach((tab) => {
      tab.addEventListener("click", handleFilterChange);
    });

    // Refresh manual
    elements.refreshBtn?.addEventListener("click", () => {
      loadOrders(false);
    });

    // Acciones de órdenes (Event Delegation)
    elements.ordersGrid?.addEventListener("click", handleOrderAction);

    // Cleanup al salir
    window.addEventListener("beforeunload", stopPolling);
  }

  // ========================================
  // Filtrado
  // ========================================

  /**
   * Maneja cambio de filtro
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
   * Aplica filtro actual a las órdenes
   */
  function applyFilter() {
    const cards = document.querySelectorAll(".kitchen-order-card");

    cards.forEach((card) => {
      const status = card.dataset.status;

      if (state.currentFilter === "all" || status === state.currentFilter) {
        card.style.display = "block";
      } else {
        card.style.display = "none";
      }
    });

    // Mostrar empty state si no hay órdenes visibles
    const visibleCards = Array.from(cards).filter(
      (card) => card.style.display !== "none"
    );
    elements.emptyState.style.display =
      visibleCards.length === 0 ? "block" : "none";
    elements.ordersGrid.style.display =
      visibleCards.length === 0 ? "none" : "grid";
  }

  // ========================================
  // Carga de Órdenes
  // ========================================

  /**
   * Carga órdenes desde el servidor
   * Pattern: Async/Await con manejo de errores
   *
   * @param {boolean} silent - Si es true, no muestra indicadores de carga
   */
  async function loadOrders(silent = true) {
    if (state.isRefreshing) return;

    state.isRefreshing = true;

    if (!silent && elements.refreshBtn) {
      elements.refreshBtn.innerHTML =
        '<i class="fas fa-sync-alt fa-spin"></i> Actualizando...';
      elements.refreshBtn.disabled = true;
    }

    try {
      const response = await fetch(
        `${APP_CONFIG.baseUrl}/orders/getKitchenOrders?status=${state.currentFilter}`
      );

      if (!response.ok) {
        throw new Error("Error al cargar órdenes");
      }

      const data = await response.json();

      if (data.success) {
        state.orders = data.orders;
        updateStats(data.stats);
        renderOrders();
        updateLastUpdate();

        if (!silent) {
          Toast.success("Órdenes actualizadas", 2000);
        }
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error("Error loading orders:", error);
      if (!silent) {
        Toast.error("Error al cargar órdenes");
      }
    } finally {
      state.isRefreshing = false;

      if (!silent && elements.refreshBtn) {
        elements.refreshBtn.innerHTML =
          '<i class="fas fa-sync-alt"></i> Actualizar';
        elements.refreshBtn.disabled = false;
      }
    }
  }

  // ========================================
  // Renderizado de Órdenes
  // ========================================

  /**
   * Renderiza órdenes en el grid
   */
  function renderOrders() {
    if (state.orders.length === 0) {
      elements.ordersGrid.style.display = "none";
      elements.emptyState.style.display = "block";
      return;
    }

    elements.ordersGrid.style.display = "grid";
    elements.emptyState.style.display = "none";

    // Agrupar órdenes por cuenta (Pattern: Data Transformation)
    const groupedOrders = groupOrdersByAccount(state.orders);

    let html = "";
    groupedOrders.forEach((group) => {
      html += createOrderCard(group);
    });

    elements.ordersGrid.innerHTML = html;
    applyFilter();
  }

  /**
   * Agrupa órdenes por cuenta/mesa
   * @param {Array} orders - Array de órdenes
   * @return {Array} Órdenes agrupadas
   */
  function groupOrdersByAccount(orders) {
    const grouped = {};

    orders.forEach((order) => {
      const key = order.account_id;

      if (!grouped[key]) {
        grouped[key] = {
          account_id: order.account_id,
          table_number: order.table_number,
          waiter_name: order.waiter_name,
          orders: [],
          oldest_time: order.waiting_minutes,
          status: order.status,
        };
      }

      grouped[key].orders.push(order);

      // Mantener el tiempo más antiguo
      if (order.waiting_minutes > grouped[key].oldest_time) {
        grouped[key].oldest_time = order.waiting_minutes;
      }

      // Status: si alguna está preparing, toda la cuenta está preparing
      if (order.status === "preparing") {
        grouped[key].status = "preparing";
      }
    });

    // Convertir a array y ordenar por tiempo de espera
    return Object.values(grouped).sort((a, b) => b.oldest_time - a.oldest_time);
  }

  /**
   * Crea HTML de tarjeta de orden
   * @param {Object} group - Grupo de órdenes
   * @return {string} HTML de la tarjeta
   */
  function createOrderCard(group) {
    const timeClass = getTimeClass(group.oldest_time);

    return `
            <div class="kitchen-order-card status-${
              group.status
            }" data-status="${group.status}" data-account="${group.account_id}">
                <div class="order-header">
                    <span class="order-table">Mesa ${escapeHtml(
                      group.table_number
                    )}</span>
                    <span class="order-time ${timeClass}">${
      group.oldest_time
    } min</span>
                </div>

                <div class="order-dishes">
                    ${group.orders
                      .map((order) => createDishItem(order))
                      .join("")}
                </div>

                <div class="order-footer">
                    <div class="order-waiter">
                        <i class="fas fa-user"></i> ${escapeHtml(
                          group.waiter_name
                        )}
                    </div>
                    <div class="order-actions">
                        ${createActionButtons(group)}
                    </div>
                </div>
            </div>
        `;
  }

  /**
   * Crea HTML de un platillo
   * @param {Object} order - Orden individual
   * @return {string} HTML del platillo
   */
  function createDishItem(order) {
    return `
            <div class="order-dish" data-order-id="${order.id}">
                <div class="dish-name-qty">
                    <strong>${escapeHtml(order.dish_name)}</strong>
                    <span class="dish-qty">x${order.quantity}</span>
                </div>
                ${
                  order.side_dish_name
                    ? `
                    <div class="dish-side">
                        <i class="fas fa-utensils"></i> con ${escapeHtml(
                          order.side_dish_name
                        )}
                    </div>
                `
                    : ""
                }
                ${
                  order.special_instructions
                    ? `
                    <div class="dish-instructions">
                        <i class="fas fa-comment"></i> ${escapeHtml(
                          order.special_instructions
                        )}
                    </div>
                `
                    : ""
                }
            </div>
        `;
  }

  /**
   * Crea botones de acción según el estado
   * @param {Object} group - Grupo de órdenes
   * @return {string} HTML de botones
   */
  function createActionButtons(group) {
    const accountId = group.account_id;

    if (group.status === "pending") {
      return `
                <button class="btn-action btn-start" data-action="start" data-account="${accountId}">
                    <i class="fas fa-fire"></i> Iniciar
                </button>
            `;
    } else if (group.status === "preparing") {
      return `
                <button class="btn-action btn-ready" data-action="ready" data-account="${accountId}">
                    <i class="fas fa-check"></i> Marcar Listo
                </button>
            `;
    }

    return "";
  }

  /**
   * Obtiene clase CSS según tiempo de espera
   * @param {number} minutes - Minutos de espera
   * @return {string} Clase CSS
   */
  function getTimeClass(minutes) {
    if (minutes >= config.urgentThreshold) {
      return "urgent";
    } else if (minutes >= config.warningThreshold) {
      return "warning";
    }
    return "normal";
  }

  // ========================================
  // Acciones de Órdenes
  // ========================================

  /**
   * Maneja clicks en acciones de órdenes
   * Pattern: Event Delegation
   *
   * @param {Event} e - Evento de click
   */
  function handleOrderAction(e) {
    const actionBtn = e.target.closest("[data-action]");
    if (!actionBtn) return;

    const action = actionBtn.dataset.action;
    const accountId = actionBtn.dataset.account;

    if (action === "start") {
      startPreparingOrders(accountId);
    } else if (action === "ready") {
      markOrdersReady(accountId);
    }
  }

  /**
   * Inicia preparación de órdenes
   * @param {number} accountId - ID de la cuenta
   */
  async function startPreparingOrders(accountId) {
    const orders = state.orders.filter((o) => o.account_id == accountId);

    try {
      // Actualizar todas las órdenes de la cuenta
      for (const order of orders) {
        await updateOrderStatus(order.id, "preparing");
      }

      Toast.success("Orden en preparación", 2000);
      loadOrders(true);
    } catch (error) {
      console.error("Error starting orders:", error);
      Toast.error("Error al iniciar preparación");
    }
  }

  /**
   * Marca órdenes como listas
   * @param {number} accountId - ID de la cuenta
   */
  async function markOrdersReady(accountId) {
    const orders = state.orders.filter((o) => o.account_id == accountId);

    try {
      // Actualizar todas las órdenes de la cuenta
      for (const order of orders) {
        await updateOrderStatus(order.id, "ready");
      }

      Toast.success("Orden lista para servir", 2000);
      loadOrders(true);
    } catch (error) {
      console.error("Error marking ready:", error);
      Toast.error("Error al marcar como listo");
    }
  }

  /**
   * Actualiza estado de una orden
   * @param {number} orderId - ID de la orden
   * @param {string} newStatus - Nuevo estado
   * @return {Promise}
   */
  async function updateOrderStatus(orderId, newStatus) {
    const formData = new FormData();
    formData.append("order_id", orderId);
    formData.append("new_status", newStatus);

    const response = await fetch(`${APP_CONFIG.baseUrl}/orders/updateStatus`, {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.message);
    }

    return data;
  }

  // ========================================
  // Estadísticas
  // ========================================

  /**
   * Actualiza estadísticas en el UI
   * @param {Object} stats - Objeto con estadísticas
   */
  function updateStats(stats) {
    elements.pendingCount.textContent = stats.pending || 0;
    elements.preparingCount.textContent = stats.preparing || 0;
    elements.readyCount.textContent = stats.ready || 0;

    const avgTime = stats.avg_prep_time ? Math.round(stats.avg_prep_time) : 0;
    elements.avgTime.textContent = `${avgTime} min`;
  }

  /**
   * Actualiza timestamp de última actualización
   */
  function updateLastUpdate() {
    state.lastUpdate = new Date();
    if (elements.lastUpdate) {
      elements.lastUpdate.textContent = `Última actualización: ${formatTime(
        state.lastUpdate
      )}`;
    }
  }

  // ========================================
  // Polling (Pattern: Polling for Real-time Updates)
  // ========================================

  /**
   * Inicia actualización automática
   */
  function startPolling() {
    state.pollingInterval = setInterval(() => {
      loadOrders(true);
    }, config.pollingDelay);
  }

  /**
   * Detiene actualización automática
   */
  function stopPolling() {
    if (state.pollingInterval) {
      clearInterval(state.pollingInterval);
      state.pollingInterval = null;
    }
  }

  // ========================================
  // Utilidades
  // ========================================

  /**
   * Formatea hora
   * @param {Date} date - Fecha a formatear
   * @return {string} Hora formateada
   */
  function formatTime(date) {
    return date.toLocaleTimeString("es-MX", {
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  /**
   * Escapa HTML para prevenir XSS
   * @param {string} text - Texto a escapar
   * @return {string} Texto escapado
   */
  function escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return String(text).replace(/[&<>"']/g, (m) => map[m]);
  }
})();
