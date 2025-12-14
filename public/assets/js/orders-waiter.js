/**
 * Orders Waiter JavaScript
 * Gestión de órdenes para meseros
 *
 * Patrón: Module Pattern para encapsulación
 * @author Mario
 * @version 1.0.0
 */

(function () {
  "use strict";

  // ========================================
  // Estado de la Aplicación (Pattern: State Management)
  // ========================================
  const state = {
    cart: [],
    currentDish: null,
    accountId: APP_CONFIG.accountId,
    isSubmitting: false,
  };

  // ========================================
  // Elementos DOM
  // ========================================
  const elements = {
    // Menú
    dishCards: document.querySelectorAll(".dish-card"),
    searchInput: document.getElementById("searchDish"),

    // Carrito
    cartItems: document.getElementById("cartItems"),
    cartSummary: document.getElementById("cartSummary"),
    cartSubtotal: document.getElementById("cartSubtotal"),
    cartTotal: document.getElementById("cartTotal"),
    clearCartBtn: document.getElementById("clearCart"),
    sendToKitchenBtn: document.getElementById("sendToKitchen"),

    // Modal
    modal: new bootstrap.Modal(document.getElementById("dishConfigModal")),
    configDishId: document.getElementById("configDishId"),
    configDishName: document.getElementById("configDishName"),
    configDishPrice: document.getElementById("configDishPrice"),
    configSideDish: document.getElementById("configSideDish"),
    configQuantity: document.getElementById("configQuantity"),
    configInstructions: document.getElementById("configInstructions"),
    sideDishGroup: document.getElementById("sideDishGroup"),
    modalSubtotal: document.getElementById("modalSubtotal"),
    decreaseQty: document.getElementById("decreaseQty"),
    increaseQty: document.getElementById("increaseQty"),
    confirmAddBtn: document.getElementById("confirmAddToCart"),
  };

  // ========================================
  // Inicialización
  // ========================================
  document.addEventListener("DOMContentLoaded", () => {
    initEventListeners();
    loadCartFromStorage();
    console.log("Orders Waiter Module Initialized");
  });

  /**
   * Inicializa event listeners
   */
  function initEventListeners() {
    // Botones de agregar platillo
    document.addEventListener("click", (e) => {
      const addBtn = e.target.closest(".btn-add-dish");
      if (addBtn) {
        e.preventDefault();
        const dishCard = addBtn.closest(".dish-card");
        openDishConfig(dishCard);
      }
    });

    // Búsqueda de platillos
    elements.searchInput?.addEventListener("input", handleSearch);

    // Controles de cantidad en modal
    elements.decreaseQty?.addEventListener("click", () => adjustQuantity(-1));
    elements.increaseQty?.addEventListener("click", () => adjustQuantity(1));
    elements.configQuantity?.addEventListener("input", updateModalSubtotal);

    // Cambio de guisado
    elements.configSideDish?.addEventListener(
      "change",
      validateSideDishSelection
    );

    // Confirmar agregar al carrito
    elements.confirmAddBtn?.addEventListener("click", addToCart);

    // Limpiar carrito
    elements.clearCartBtn?.addEventListener("click", clearCart);

    // Enviar a cocina
    elements.sendToKitchenBtn?.addEventListener("click", sendToKitchen);

    // Remover items del carrito (Event Delegation)
    elements.cartItems?.addEventListener("click", (e) => {
      const removeBtn = e.target.closest(".btn-remove-item");
      if (removeBtn) {
        const index = parseInt(removeBtn.dataset.index);
        removeFromCart(index);
      }
    });
  }

  // ========================================
  // Gestión de Platillos
  // ========================================

  /**
   * Abre modal de configuración de platillo
   * @param {HTMLElement} dishCard - Card del platillo
   */
  function openDishConfig(dishCard) {
    const dishId = dishCard.dataset.dishId;
    const dishName = dishCard.dataset.dishName;
    const dishPrice = parseFloat(dishCard.dataset.dishPrice);
    const requiresSide = dishCard.dataset.requiresSide === "1";

    // Guardar platillo actual en estado
    state.currentDish = {
      id: dishId,
      name: dishName,
      price: dishPrice,
      requiresSide: requiresSide,
    };

    // Poblar modal
    elements.configDishId.value = dishId;
    elements.configDishName.textContent = dishName;
    elements.configDishPrice.textContent = `$${dishPrice.toFixed(2)}`;
    elements.configQuantity.value = 1;
    elements.configSideDish.value = "";
    elements.configInstructions.value = "";

    // Mostrar/ocultar grupo de guisado
    if (requiresSide) {
      elements.sideDishGroup.style.display = "block";
      elements.configSideDish.required = true;
    } else {
      elements.sideDishGroup.style.display = "none";
      elements.configSideDish.required = false;
    }

    updateModalSubtotal();
    elements.modal.show();
  }

  /**
   * Ajusta cantidad en el modal
   * @param {number} delta - Incremento (+1 o -1)
   */
  function adjustQuantity(delta) {
    const currentQty = parseInt(elements.configQuantity.value);
    const newQty = Math.max(1, Math.min(20, currentQty + delta));
    elements.configQuantity.value = newQty;
    updateModalSubtotal();
  }

  /**
   * Actualiza subtotal en el modal
   */
  function updateModalSubtotal() {
    if (!state.currentDish) return;

    const quantity = parseInt(elements.configQuantity.value) || 1;
    const subtotal = state.currentDish.price * quantity;
    elements.modalSubtotal.textContent = `$${subtotal.toFixed(2)}`;
  }

  /**
   * Valida selección de guisado
   */
  function validateSideDishSelection() {
    const sideDishValue = elements.configSideDish.value;

    if (state.currentDish?.requiresSide && !sideDishValue) {
      elements.configSideDish.classList.add("is-invalid");
      return false;
    }

    elements.configSideDish.classList.remove("is-invalid");
    return true;
  }

  /**
   * Búsqueda de platillos
   * @param {Event} e - Evento de input
   */
  function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase().trim();

    document.querySelectorAll(".dish-card").forEach((card) => {
      const dishName = card.dataset.dishName.toLowerCase();
      const matches = dishName.includes(searchTerm);
      card.style.display = matches ? "block" : "none";
    });
  }

  // ========================================
  // Gestión del Carrito
  // ========================================

  /**
   * Agrega platillo al carrito
   */
  function addToCart() {
    // Validar guisado si es requerido
    if (state.currentDish.requiresSide && !validateSideDishSelection()) {
      Toast.warning("Debes seleccionar un guisado para este platillo");
      return;
    }

    const quantity = parseInt(elements.configQuantity.value);
    const sideDishId = elements.configSideDish.value || null;
    const sideDishName = sideDishId
      ? elements.configSideDish.options[elements.configSideDish.selectedIndex]
          .text
      : null;
    const instructions = elements.configInstructions.value.trim();

    // Crear item del carrito
    const cartItem = {
      dish_id: state.currentDish.id,
      dish_name: state.currentDish.name,
      price: state.currentDish.price,
      quantity: quantity,
      side_dish_id: sideDishId,
      side_dish_name: sideDishName,
      special_instructions: instructions,
      subtotal: state.currentDish.price * quantity,
    };

    // Agregar al estado
    state.cart.push(cartItem);

    // Actualizar UI
    renderCart();
    saveCartToStorage();

    // Cerrar modal
    elements.modal.hide();

    // Notificación
    Toast.success(`${cartItem.dish_name} agregado al carrito`);
  }

  /**
   * Remueve item del carrito
   * @param {number} index - Índice del item
   */
  function removeFromCart(index) {
    const item = state.cart[index];

    if (confirm(`¿Eliminar ${item.dish_name} del carrito?`)) {
      state.cart.splice(index, 1);
      renderCart();
      saveCartToStorage();
      Toast.info("Item eliminado del carrito");
    }
  }

  /**
   * Limpia todo el carrito
   */
  function clearCart() {
    if (state.cart.length === 0) return;

    Toast.confirm("¿Estás seguro de limpiar todo el carrito?", () => {
      state.cart = [];
      renderCart();
      saveCartToStorage();
      Toast.success("Carrito limpiado");
    });
  }

  /**
   * Renderiza el carrito en la UI
   */
  function renderCart() {
    const isEmpty = state.cart.length === 0;

    if (isEmpty) {
      elements.cartItems.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Agrega platillos para comenzar</p>
                </div>
            `;
      elements.cartSummary.style.display = "none";
      elements.clearCartBtn.style.display = "none";
      elements.sendToKitchenBtn.disabled = true;
      return;
    }

    // Renderizar items
    let html = "";
    let subtotal = 0;

    state.cart.forEach((item, index) => {
      subtotal += item.subtotal;

      html += `
                <div class="cart-item">
                    <div class="cart-item-header">
                        <span class="cart-item-name">${escapeHtml(
                          item.dish_name
                        )}</span>
                        <button class="btn-remove-item" data-index="${index}" title="Eliminar">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    ${
                      item.side_dish_name
                        ? `
                        <div class="cart-item-details">
                            <i class="fas fa-utensils"></i> con ${escapeHtml(
                              item.side_dish_name
                            )}
                        </div>
                    `
                        : ""
                    }
                    ${
                      item.special_instructions
                        ? `
                        <div class="cart-item-details">
                            <i class="fas fa-comment"></i> ${escapeHtml(
                              item.special_instructions
                            )}
                        </div>
                    `
                        : ""
                    }
                    <div class="cart-item-footer">
                        <span class="cart-item-quantity">Cantidad: ${
                          item.quantity
                        }</span>
                        <span class="cart-item-price">$${item.subtotal.toFixed(
                          2
                        )}</span>
                    </div>
                </div>
            `;
    });

    elements.cartItems.innerHTML = html;

    // Actualizar resumen
    elements.cartSubtotal.textContent = `$${subtotal.toFixed(2)}`;
    elements.cartTotal.textContent = `$${subtotal.toFixed(2)}`;
    elements.cartSummary.style.display = "block";
    elements.clearCartBtn.style.display = "block";
    elements.sendToKitchenBtn.disabled = false;
  }

  // ========================================
  // Envío a Cocina
  // ========================================

  /**
   * Envía orden a cocina
   * Pattern: Async/Await con manejo de errores
   */
  async function sendToKitchen() {
    if (state.cart.length === 0) {
      Toast.warning("El carrito está vacío");
      return;
    }

    if (state.isSubmitting) return;

    state.isSubmitting = true;
    elements.sendToKitchenBtn.disabled = true;
    elements.sendToKitchenBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    try {
      const formData = new FormData();
      formData.append("account_id", state.accountId);

      // Preparar items para envío
      state.cart.forEach((item, index) => {
        formData.append(`items[${index}][dish_id]`, item.dish_id);
        formData.append(`items[${index}][quantity]`, item.quantity);

        if (item.side_dish_id) {
          formData.append(`items[${index}][side_dish_id]`, item.side_dish_id);
        }

        if (item.special_instructions) {
          formData.append(
            `items[${index}][special_instructions]`,
            item.special_instructions
          );
        }
      });

      const response = await fetch(`${APP_CONFIG.baseUrl}/orders/store`, {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        Toast.success(data.message, 3000);

        // Limpiar carrito
        state.cart = [];
        renderCart();
        saveCartToStorage();

        // Recargar página después de 2 segundos para ver órdenes actualizadas
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      } else {
        throw new Error(data.message || "Error al enviar orden");
      }
    } catch (error) {
      console.error("Error sending order:", error);
      Toast.error(error.message || "Error al enviar orden a cocina");
    } finally {
      state.isSubmitting = false;
      elements.sendToKitchenBtn.disabled = false;
      elements.sendToKitchenBtn.innerHTML =
        '<i class="fas fa-paper-plane"></i> Enviar a Cocina';
    }
  }

  // ========================================
  // Persistencia Local (Pattern: Local Storage)
  // ========================================

  /**
   * Guarda carrito en localStorage
   */
  function saveCartToStorage() {
    try {
      localStorage.setItem(
        `cart_${state.accountId}`,
        JSON.stringify(state.cart)
      );
    } catch (error) {
      console.error("Error saving cart:", error);
    }
  }

  /**
   * Carga carrito desde localStorage
   */
  function loadCartFromStorage() {
    try {
      const saved = localStorage.getItem(`cart_${state.accountId}`);
      if (saved) {
        state.cart = JSON.parse(saved);
        renderCart();
      }
    } catch (error) {
      console.error("Error loading cart:", error);
      state.cart = [];
    }
  }

  // ========================================
  // Utilidades
  // ========================================

  /**
   * Escapa HTML para prevenir XSS
   * Pattern: Security by Design
   *
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
    return text.replace(/[&<>"']/g, (m) => map[m]);
  }
})();
