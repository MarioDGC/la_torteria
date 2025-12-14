/**
 * Account Detail JavaScript
 * Gestión de cuenta individual
 *
 * @author Mario
 * @version 1.0.0
 */

(function () {
  "use strict";

  // ========================================
  // Configuración
  // ========================================
  const config = {
    refreshInterval: 10000, // 10 segundos
  };

  let refreshTimer = null;

  // ========================================
  // Inicialización
  // ========================================
  document.addEventListener("DOMContentLoaded", () => {
    initEventListeners();
    startAutoRefresh();
    console.log("Account Detail Module Initialized");
  });

  /**
   * Inicializa event listeners
   */
  function initEventListeners() {
    // Marcar como servido (Event Delegation)
    document.addEventListener("click", (e) => {
      const btn = e.target.closest(".btn-mark-served");
      if (btn) {
        const orderId = btn.dataset.orderId;
        markAsServed(orderId);
      }
    });

    // Cleanup
    window.addEventListener("beforeunload", stopAutoRefresh);
  }

  // ========================================
  // Marcar como Servido
  // ========================================

  /**
   * Marca una orden como servida
   * @param {number} orderId - ID de la orden
   */
  async function markAsServed(orderId) {
    try {
      const formData = new FormData();
      formData.append("order_id", orderId);

      const response = await fetch(`${APP_CONFIG.baseUrl}/orders/markServed`, {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        Toast.success(data.message, 2000);

        // Recargar página para actualizar vista
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error("Error marking as served:", error);
      Toast.error("Error al marcar como servido");
    }
  }

  // ========================================
  // Auto Refresh
  // ========================================

  /**
   * Inicia actualización automática
   */
  function startAutoRefresh() {
    refreshTimer = setInterval(() => {
      // Recargar página silenciosamente
      window.location.reload();
    }, config.refreshInterval);
  }

  /**
   * Detiene actualización automática
   */
  function stopAutoRefresh() {
    if (refreshTimer) {
      clearInterval(refreshTimer);
      refreshTimer = null;
    }
  }
})();
