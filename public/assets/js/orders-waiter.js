/**
 * Orders Management - Waiter View
 * Gestión de órdenes desde la vista del mesero
 *
 * @author Mario
 * @version 1.0.0
 */

(function () {
  ("use strict");

  // ========================================
  // Estado Global
  // ========================================
  const state = {
    currentTable: null,
    currentAccount: null,
    dishes: {}, // { categoryId: [dishes] }
    sideDishes: [],
    cart: [],
    activeCategory: null,
  };

  // ========================================
  // Elementos DOM
  // ========================================
  const elements = {
    // Category tabs
    categoryTabs: document.querySelectorAll(".category-tab"),
    dishesGrid: document.getElementById("dishesGrid"),

    // Cart
    cartItems: document.getElementById("cartItems"),
    emptyCart: document.getElementById("emptyCart"),
    cartSubtotal: document.getElementById("cartSubtotal"),
    cartTax: document.getElementById("cartTax"),
    cartTotal: document.getElementById("cartTotal"),
    btnClearCart: document.getElementById("btnClearCart"),
    btnSubmitOrder: document.getElementById("btnSubmitOrder"),

    // Modals
    configModal: null,
    detailsModal: null,
  };

  // ========================================
  // Inicialización
  // ========================================
  document.addEventListener("DOMContentLoaded", () => {
    initModals();
    initEventListeners();
    loadInitialData();
    console.log("✅ Orders Waiter Module initialized");
  });

  /**
   * Inicializa los modales de Bootstrap
   */
  function initModals() {
    const configModalElement = document.getElementById("dishConfigModal");
    const detailsModalElement = document.getElementById("dishDetailsModal");

    if (configModalElement) {
      elements.configModal = new bootstrap.Modal(configModalElement);
      console.log("✅ Modal de configuración inicializado");
    } else {
      console.warn("⚠️ Modal de configuración no encontrado");
    }

    if (detailsModalElement) {
      elements.detailsModal = new bootstrap.Modal(detailsModalElement);
      console.log("✅ Modal de detalles inicializado");
    } else {
      console.warn("⚠️ Modal de detalles no encontrado");
    }
  }

  /**
   * Configura todos los event listeners
   */
  function initEventListeners() {
    console.log("🎧 Inicializando event listeners...");

    // Category tabs
    elements.categoryTabs.forEach((tab) => {
      // Remover listener anterior si existe (prevenir duplicados)
      tab.removeEventListener("click", handleCategoryChange);
      tab.addEventListener("click", handleCategoryChange);
    });

    // Cart actions
    if (elements.btnClearCart) {
      elements.btnClearCart.removeEventListener("click", handleClearCart);
      elements.btnClearCart.addEventListener("click", handleClearCart);
    }

    if (elements.btnSubmitOrder) {
      elements.btnSubmitOrder.removeEventListener("click", handleSubmitOrder);
      elements.btnSubmitOrder.addEventListener("click", handleSubmitOrder);
    }

    // Dish config modal
    const btnDecrease = document.getElementById("btnDecrease");
    const btnIncrease = document.getElementById("btnIncrease");
    const configQuantity = document.getElementById("configQuantity");
    const btnAddToCart = document.getElementById("btnAddToCart");

    if (btnDecrease) {
      btnDecrease.removeEventListener("click", decreaseQuantity);
      btnDecrease.addEventListener("click", decreaseQuantity);
    }

    if (btnIncrease) {
      btnIncrease.removeEventListener("click", increaseQuantity);
      btnIncrease.addEventListener("click", increaseQuantity);
    }

    if (configQuantity) {
      configQuantity.removeEventListener("input", handleQuantityInput);
      configQuantity.addEventListener("input", handleQuantityInput);
    }

    if (btnAddToCart) {
      btnAddToCart.removeEventListener("click", handleAddToCart);
      btnAddToCart.addEventListener("click", handleAddToCart);
    }

    const btnConfirmAdd = document.getElementById("confirmAddToCart");
    if (btnConfirmAdd) {
      btnConfirmAdd.removeEventListener("click", handleAddToCart);
      btnConfirmAdd.addEventListener("click", handleAddToCart);
    }

    // ⚠️ CRÍTICO: Solo un listener para document
    // Remover el anterior si existe
    document.removeEventListener("click", handleDynamicClicks);
    document.addEventListener("click", handleDynamicClicks);

    console.log("✅ Event listeners inicializados");
  }

  // Funciones separadas para los botones (mejor para remover listeners)
  function decreaseQuantity() {
    adjustQuantity(-1);
  }

  function increaseQuantity() {
    adjustQuantity(1);
  }

  /**
   * Maneja clicks en elementos dinámicos (Event Delegation Pattern)
   * @param {Event} e - Evento de click
   */
  function handleDynamicClicks(e) {
    // Botón "Ver detalles"
    if (e.target.closest(".btn-dish-details")) {
      e.stopPropagation();
      const dishCard = e.target.closest(".dish-card");
      openDishDetails(dishCard);
    }

    // Botón "Agregar" desde el modal de detalles
    else if (e.target.closest("#btnAddFromDetails")) {
      const dish = dishDetailsModal._currentDish;
      if (dish) {
        elements.detailsModal.hide();
        openDishConfig(dish);
      }
    }

    // Botón "Agregar" en dish card
    else if (e.target.closest(".btn-add-dish")) {
      e.stopPropagation();
      const dishCard = e.target.closest(".dish-card");
      openDishConfig(dishCard);
    }

    // Botón remover item del carrito
    else if (e.target.closest(".btn-remove-item")) {
      const index = parseInt(
        e.target.closest(".btn-remove-item").dataset.index
      );
      removeFromCart(index);
    }
  }

  // ========================================
  // Carga de Datos Inicial
  // ========================================

  /**
   * Carga todos los datos necesarios al iniciar
   */
  async function loadInitialData() {
    console.log("🔄 Iniciando carga de datos...");

    try {
      // Obtener datos de la mesa desde el HTML (pasados por PHP)
      const tableData = document.getElementById("tableData");
      if (tableData) {
        state.currentTable = {
          id: tableData.dataset.tableId,
          number: tableData.dataset.tableNumber,
        };
        state.currentAccount = {
          id: tableData.dataset.accountId,
        };
        console.log("✅ Datos de mesa cargados:", state.currentTable);
      } else {
        console.error("❌ No se encontró elemento #tableData");
      }

      // Cargar platillos y guisados
      await Promise.all([loadDishes(), loadSideDishes()]);

      console.log("✅ Platillos cargados:", state.dishes);
      console.log("✅ Guisados cargados:", state.sideDishes);

      // Activar primera categoría
      if (elements.categoryTabs.length > 0) {
        elements.categoryTabs[0].click();
      } else {
        console.warn("⚠️ No hay tabs de categorías");
      }
    } catch (error) {
      console.error("❌ Error loading initial data:", error);
      Toast.error("Error al cargar datos iniciales");
    }
  }

  /**
   * Carga platillos del servidor
   */
  async function loadDishes() {
    try {
      console.log("📡 Cargando platillos...");

      const response = await fetch(`${APP_CONFIG.baseUrl}/orders/getDishes`);

      if (!response.ok) {
        const text = await response.text();
        console.error("❌ Respuesta del servidor:", text);
        throw new Error(`HTTP ${response.status}: Error al cargar platillos`);
      }

      const data = await response.json();
      console.log("📦 Datos recibidos:", data);

      if (data.success) {
        // Agrupar por categoría
        state.dishes = data.dishes.reduce((acc, dish) => {
          if (!acc[dish.category_id]) {
            acc[dish.category_id] = [];
          }
          acc[dish.category_id].push(dish);
          return acc;
        }, {});

        console.log("✅ Platillos procesados:", state.dishes);
      } else {
        throw new Error(data.message || "Error desconocido");
      }
    } catch (error) {
      console.error("❌ Error loading dishes:", error);
      Toast.error("Error al cargar platillos: " + error.message);
      throw error; // Re-lanzar para que loadInitialData lo capture
    }
  }

  /**
   * Carga guisados disponibles
   */
  async function loadSideDishes() {
    try {
      const response = await fetch(
        `${APP_CONFIG.baseUrl}/orders/getSideDishes`
      );

      if (!response.ok) {
        throw new Error("Error al cargar guisados");
      }

      const data = await response.json();

      if (data.success) {
        state.sideDishes = data.sideDishes || [];
      }
    } catch (error) {
      console.error("Error loading side dishes:", error);
      // No mostrar error al usuario, los guisados son opcionales
    }
  }

  // ========================================
  // Gestión de Categorías
  // ========================================

  /**
   * Maneja el cambio de categoría
   * @param {Event} e - Evento de click
   */
  function handleCategoryChange(e) {
    const categoryId = e.currentTarget.dataset.categoryId;

    // Actualizar tabs activos
    elements.categoryTabs.forEach((tab) => tab.classList.remove("active"));
    e.currentTarget.classList.add("active");

    // Actualizar estado
    state.activeCategory = categoryId;

    // Renderizar platillos de la categoría
    renderDishes(categoryId);
  }

  /**
   * Renderiza los platillos de una categoría
   * @param {string} categoryId - ID de la categoría
   */
  function renderDishes(categoryId) {
    const dishes = state.dishes[categoryId] || [];

    if (dishes.length === 0) {
      elements.dishesGrid.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-utensils fa-3x"></i>
                        <h4>No hay platillos en esta categoría</h4>
                    </div>
                </div>
            `;
      return;
    }

    // Renderizar cards de platillos
    elements.dishesGrid.innerHTML = dishes
      .map((dish) => createDishCard(dish))
      .join("");
  }

  /**
   * Crea el HTML de una tarjeta de platillo
   * @param {Object} dish - Datos del platillo
   * @returns {string} HTML de la tarjeta
   */
  function createDishCard(dish) {
    const isAvailable =
      dish.is_available === "1" ||
      dish.is_available === 1 ||
      dish.is_available === true;
    const price = parseFloat(dish.price);

    // ✅ Log para verificar datos del platillo
    console.log("🎨 Creando card para platillo:", {
      id: dish.id,
      name: dish.name,
      isAvailable: isAvailable,
    });

    return `
        <div class="col-12 col-md-6 col-lg-4">
            <div class="dish-card ${!isAvailable ? "unavailable" : ""}" 
                 data-dish-id="${dish.id}">
                <div class="row g-2 align-items-center">
                    <!-- Nombre y precio -->
                    <div class="col-8">
                        <h6 class="dish-name">${escapeHtml(dish.name)}</h6>
                    </div>
                    <div class="col-4 text-end">
                        <span class="dish-price">${formatCurrency(price)}</span>
                    </div>

                    <!-- Botones de acción -->
                    <div class="col-8">
                        <button class="btn-dish-details" 
                                title="Ver detalles"
                                ${!isAvailable ? "disabled" : ""}>
                            <i class="fas fa-info-circle"></i> Detalles
                        </button>
                    </div>
                    <div class="col-4 text-end">
                        <button class="btn-add-dish" 
                                title="Agregar al carrito"
                                ${!isAvailable ? "disabled" : ""}>
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
  }

  // ========================================
  // Modal de Detalles del Platillo
  // ========================================

  // Variable global para el modal
  const dishDetailsModal = {
    _currentDish: null,
  };

  /**
   * Abre el modal con los detalles de un platillo
   * @param {HTMLElement} dishCard - Elemento .dish-card
   */
  function openDishDetails(dishCard) {
    console.log("🎯 openDishDetails - dishCard:", dishCard);

    const dishId = dishCard.dataset.dishId;
    console.log("🆔 dishId extraído:", dishId, "(type:", typeof dishId, ")");
    console.log("📋 Todos los datasets:", dishCard.dataset);

    const dish = getDishById(dishId);

    if (!dish) {
      Toast.error("No se encontró información del platillo");
      return;
    }

    // ✅ Verificar que el modal existe
    if (!elements.detailsModal) {
      console.error("❌ Modal de detalles no está inicializado");
      Toast.error("Error al abrir modal de detalles");
      return;
    }

    // Poblar modal con datos
    populateDishDetailsModal(dish);

    // Abrir modal
    elements.detailsModal.show();

    // Guardar referencia para agregar al carrito desde el modal
    dishDetailsModal._currentDish = dish;

    console.log("✅ Modal de detalles abierto para:", dish.name);
  }

  /**
   * Puebla el modal de detalles con información del platillo
   * @param {Object} dish - Datos del platillo
   */
  function populateDishDetailsModal(dish) {
    console.log("📝 Poblando modal con platillo:", dish);

    // Nombre del platillo
    const titleElement = document.getElementById("detailDishName");
    if (titleElement) {
      titleElement.textContent = dish.name;
    }

    // Descripción
    const descSection = document.getElementById("detailDescriptionSection");
    const descText = document.getElementById("detailDishDescription");

    if (descText && descSection) {
      if (dish.description && dish.description.trim()) {
        descText.textContent = dish.description;
        descSection.style.display = "block";
      } else {
        // Si no hay descripción, ocultar la sección
        descSection.style.display = "none";
      }
    }

    // Alerta de guisado requerido
    const sideDishAlert = document.getElementById("detailSideDishAlert");
    if (sideDishAlert) {
      const requiresSideDish =
        dish.requires_side_dish === "1" ||
        dish.requires_side_dish === 1 ||
        dish.requires_side_dish === true;
      sideDishAlert.style.display = requiresSideDish ? "flex" : "none";
    }
  }

  /**
   * Obtiene datos de un platillo por su ID
   * @param {string|number} dishId - ID del platillo
   * @returns {Object|null} Datos del platillo
   */
  function getDishById(dishId) {
    console.log(
      "🔍 Buscando platillo con ID:",
      dishId,
      "(type:",
      typeof dishId,
      ")"
    );
    console.log("📦 Estado de dishes:", state.dishes);

    // Guard: validar que dishId existe
    if (!dishId) {
      console.error("❌ dishId es null o undefined");
      return null;
    }

    // Guard: validar que hay platillos cargados
    if (!state.dishes || Object.keys(state.dishes).length === 0) {
      console.error("❌ No hay platillos cargados en el estado");
      return null;
    }

    // ✅ Convertir a string para comparación consistente
    const searchId = String(dishId);

    // Iterar sobre cada categoría
    for (const [categoryId, categoryDishes] of Object.entries(state.dishes)) {
      console.log(`🔎 Buscando en categoría ${categoryId}:`, categoryDishes);

      // Buscar en los platillos de esta categoría
      const dish = categoryDishes.find((d) => String(d.id) === searchId);

      if (dish) {
        console.log("✅ Platillo encontrado:", dish);
        return dish;
      }
    }

    console.error("❌ Platillo no encontrado con ID:", dishId);
    console.log("📋 IDs disponibles:", getAllDishIds());
    return null;
  }

  /**
   * Función helper para debugging - obtiene todos los IDs disponibles
   * @returns {Array} Lista de todos los IDs de platillos
   */
  function getAllDishIds() {
    const ids = [];
    for (const categoryDishes of Object.values(state.dishes)) {
      categoryDishes.forEach((d) => ids.push(d.id));
    }
    return ids;
  }

  // ========================================
  // Modal de Configuración de Platillo
  // ========================================

  // Variable global para el modal de configuración
  const configModal = {
    _currentDish: null,
    _quantity: 1,
  };

  /**
   * Abre el modal de configuración de platillo
   * @param {HTMLElement|Object} source - Elemento .dish-card o datos del platillo
   */
  function openDishConfig(source) {
    console.log("🔧 openDishConfig llamado con:", source);

    let dish;

    // Si es un elemento HTML, extraer datos
    if (source instanceof HTMLElement) {
      const dishId = source.dataset.dishId;
      console.log("📌 Extrayendo dishId:", dishId);

      if (!dishId) {
        console.error("❌ El elemento no tiene data-dish-id");
        Toast.error("Error: Platillo sin identificador");
        return;
      }

      dish = getDishById(dishId);
    }
    // Si ya es un objeto, usarlo directamente
    else if (typeof source === "object" && source !== null) {
      dish = source;
      console.log("📦 Usando objeto directamente:", dish);
    } else {
      console.error("❌ Tipo de source no válido:", typeof source);
      Toast.error("Error: Tipo de dato inválido");
      return;
    }

    if (!dish) {
      console.error("❌ No se encontró el platillo");
      Toast.error("No se encontró información del platillo");
      return;
    }

    // ✅ Verificar que el modal existe
    if (!elements.configModal) {
      console.error("❌ Modal de configuración no está inicializado");
      Toast.error("Error al abrir modal de configuración");
      return;
    }

    // ✅ Guardar platillo actual en el estado del modal
    configModal._currentDish = dish;
    configModal._quantity = 1;

    // ✅ Poblar campos del modal
    document.getElementById("configDishId").value = dish.id;
    document.getElementById("configDishName").textContent = dish.name;
    document.getElementById("configDishPrice").textContent = formatCurrency(
      parseFloat(dish.price)
    );
    document.getElementById("configQuantity").value = 1;
    document.getElementById("configInstructions").value = "";

    // ✅ Mostrar/ocultar select de guisados
    const sideDishGroup = document.getElementById("sideDishGroup");
    const requiresSide =
      dish.requires_side_dish === "1" ||
      dish.requires_side_dish === 1 ||
      dish.requires_side_dish === true;

    if (requiresSide) {
      sideDishGroup.style.display = "block";
      document.getElementById("configSideDish").required = true;
      populateSideDishSelect();
    } else {
      sideDishGroup.style.display = "none";
      document.getElementById("configSideDish").required = false;
    }

    // ✅ Actualizar resumen
    updateModalSummary();

    // ✅ Abrir modal
    elements.configModal.show();

    console.log("✅ Modal de configuración abierto para:", dish.name);
  }

  /**
   * Puebla el select de guisados
   */
  function populateSideDishSelect() {
    const select = document.getElementById("configSideDish");
    select.innerHTML = '<option value="">-- Seleccionar guisado --</option>';

    state.sideDishes
      .filter(
        (sd) =>
          sd.is_available === "1" ||
          sd.is_available === 1 ||
          sd.is_available === true
      )
      .forEach((sideDish) => {
        const option = document.createElement("option");
        option.value = sideDish.id;
        option.textContent = sideDish.name;
        select.appendChild(option);
      });
  }

  /**
   * Actualiza el resumen del modal
   */
  function updateModalSummary() {
    const dish = configModal._currentDish;
    const quantity = configModal._quantity;
    const price = parseFloat(dish.price);
    const total = price * quantity;

    document.getElementById("modalSubtotal").textContent =
      formatCurrency(total);
  }

  /**
   * Ajusta la cantidad en el modal
   * @param {number} delta - Cambio en la cantidad (-1 o +1)
   */
  function adjustQuantity(delta) {
    const input = document.getElementById("configQuantity");
    let newValue = parseInt(input.value) + delta;

    if (newValue < 1) newValue = 1;
    if (newValue > 99) newValue = 99;

    input.value = newValue;
    configModal._quantity = newValue;
    updateModalSummary();
  }

  /**
   * Maneja input manual de cantidad
   * @param {Event} e - Evento de input
   */
  function handleQuantityInput(e) {
    let value = parseInt(e.target.value);

    if (isNaN(value) || value < 1) value = 1;
    if (value > 99) value = 99;

    e.target.value = value;
    configModal._quantity = value;
    updateModalSummary();
  }

  /**
   * Actualiza el resumen del modal
   */
  function updateModalSummary() {
    const dish = configModal._currentDish;
    const quantity = configModal._quantity;
    const price = parseFloat(dish.price);
    const total = price * quantity;

    document.getElementById("configSummaryQuantity").textContent = quantity;
    document.getElementById("configSummaryTotal").textContent =
      formatCurrency(total);
  }

  /**
   * Agrega el platillo configurado al carrito
   */
  function handleAddToCart() {
    const dish = configModal._currentDish;
    const quantity = configModal._quantity;

    // Validar guisado si es requerido
    if (dish.requires_side_dish === "1") {
      const sideDishId = document.getElementById("sideDishId").value;
      if (!sideDishId) {
        Toast.warning("Por favor selecciona un guisado");
        return;
      }
    }

    // Crear item del carrito
    const cartItem = {
      dishId: dish.id,
      dishName: dish.name,
      price: parseFloat(dish.price),
      quantity: quantity,
      sideDishId:
        dish.requires_side_dish === "1"
          ? document.getElementById("sideDishId").value
          : null,
      sideDishName:
        dish.requires_side_dish === "1"
          ? getSideDishName(document.getElementById("sideDishId").value)
          : null,
      specialInstructions: document
        .getElementById("specialInstructions")
        .value.trim(),
    };

    // Agregar al carrito
    state.cart.push(cartItem);

    // Actualizar UI del carrito
    renderCart();

    // Cerrar modal
    elements.configModal.hide();

    // Notificación
    Toast.success(`${dish.name} agregado al carrito`);
  }

  /**
   * Obtiene el nombre de un guisado por su ID
   * @param {string} sideDishId - ID del guisado
   * @returns {string|null} Nombre del guisado
   */
  function getSideDishName(sideDishId) {
    const sideDish = state.sideDishes.find((sd) => sd.id === sideDishId);
    return sideDish ? sideDish.name : null;
  }

  // ========================================
  // Gestión del Carrito
  // ========================================

  /**
   * Renderiza el contenido del carrito
   */
  function renderCart() {
    if (state.cart.length === 0) {
      elements.emptyCart.style.display = "block";
      elements.cartItems.style.display = "none";
      document.getElementById("cartSummary").style.display = "none";
      elements.btnSubmitOrder.disabled = true;
      return;
    }

    elements.emptyCart.style.display = "none";
    elements.cartItems.style.display = "block";
    document.getElementById("cartSummary").style.display = "block";
    elements.btnSubmitOrder.disabled = false;

    // Renderizar items
    elements.cartItems.innerHTML = state.cart
      .map((item, index) => createCartItemHTML(item, index))
      .join("");

    // Actualizar totales
    updateCartSummary();
  }

  /**
   * Crea el HTML de un item del carrito
   * @param {Object} item - Item del carrito
   * @param {number} index - Índice en el array
   * @returns {string} HTML del item
   */
  function createCartItemHTML(item, index) {
    const subtotal = item.price * item.quantity;

    return `
            <div class="cart-item">
                <div class="cart-item-header">
                    <span class="cart-item-name">${escapeHtml(
                      item.dishName
                    )}</span>
                    <button class="btn-remove-item" data-index="${index}" title="Eliminar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                ${
                  item.sideDishName
                    ? `
                    <div class="cart-item-details">
                        <i class="fas fa-utensils"></i> ${escapeHtml(
                          item.sideDishName
                        )}
                    </div>
                `
                    : ""
                }
                
                ${
                  item.specialInstructions
                    ? `
                    <div class="cart-item-details">
                        <i class="fas fa-comment"></i> ${escapeHtml(
                          item.specialInstructions
                        )}
                    </div>
                `
                    : ""
                }
                
                <div class="cart-item-footer">
                    <span class="cart-item-quantity">Cantidad: ${
                      item.quantity
                    }</span>
                    <span class="cart-item-price">${formatCurrency(
                      subtotal
                    )}</span>
                </div>
            </div>
        `;
  }

  /**
   * Actualiza el resumen de totales del carrito
   */
  function updateCartSummary() {
    const subtotal = state.cart.reduce(
      (sum, item) => sum + item.price * item.quantity,
      0
    );
    const tax = subtotal * 0.16; // 16% IVA
    const total = subtotal + tax;

    elements.cartSubtotal.textContent = formatCurrency(subtotal);
    elements.cartTax.textContent = formatCurrency(tax);
    elements.cartTotal.textContent = formatCurrency(total);
  }

  /**
   * Remueve un item del carrito
   * @param {number} index - Índice del item
   */
  function removeFromCart(index) {
    state.cart.splice(index, 1);
    renderCart();
    Toast.info("Platillo eliminado del carrito");
  }

  /**
   * Limpia todo el carrito
   */
  function handleClearCart() {
    if (state.cart.length === 0) return;

    Toast.confirm("¿Estás seguro de vaciar el carrito?", () => {
      state.cart = [];
      renderCart();
      Toast.success("Carrito vacío");
    });
  }

  // ========================================
  // Envío de Orden
  // ========================================

  /**
   * Envía la orden al servidor
   */
  async function handleSubmitOrder() {
    if (state.cart.length === 0) {
      Toast.warning("El carrito está vacío");
      return;
    }

    if (!state.currentAccount || !state.currentAccount.id) {
      Toast.error("No se encontró información de la cuenta");
      return;
    }

    // Deshabilitar botón
    elements.btnSubmitOrder.disabled = true;
    elements.btnSubmitOrder.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    try {
      const response = await fetch(`${APP_CONFIG.baseUrl}/orders/create`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          account_id: state.currentAccount.id,
          items: state.cart,
        }),
      });

      const data = await response.json();

      if (data.success) {
        Toast.success("Orden enviada exitosamente");

        // Limpiar carrito
        state.cart = [];
        renderCart();

        // Opcional: Redirigir o actualizar vista
        setTimeout(() => {
          window.location.href = `${APP_CONFIG.baseUrl}/orders/view/${state.currentAccount.id}`;
        }, 1500);
      } else {
        throw new Error(data.message || "Error al enviar orden");
      }
    } catch (error) {
      console.error("Error submitting order:", error);
      Toast.error(error.message || "Error al enviar la orden");
    } finally {
      // Rehabilitar botón
      elements.btnSubmitOrder.disabled = false;
      elements.btnSubmitOrder.innerHTML =
        '<i class="fas fa-paper-plane"></i> Enviar Orden';
    }
  }

  // ========================================
  // Utilidades
  // ========================================

  /**
   * Formatea un número como moneda
   * @param {number} amount - Cantidad
   * @returns {string} Cantidad formateada
   */
  function formatCurrency(amount) {
    return new Intl.NumberFormat("es-MX", {
      style: "currency",
      currency: "MXN",
    }).format(amount);
  }

  /**
   * Escapa HTML para prevenir XSS
   * @param {string} text - Texto a escapar
   * @returns {string} Texto escapado
   */
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
})();
