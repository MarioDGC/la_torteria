/**
 * Dashboard JavaScript - Sistema Restaurante
 * Manejo de interacciones y datos del dashboard
 */

// ========================================
// Variables Globales
// ========================================
const sidebar = document.getElementById('sidebar');
const toggleSidebarBtn = document.getElementById('toggleSidebar');
const notificationBtn = document.getElementById('notificationBtn');
const logoutBtn = document.getElementById('logoutBtn');

// ========================================
// Datos de Ejemplo (simulación)
// ========================================
const dashboardData = {
    ventasHoy: 15420.50,
    ordenesHoy: 47,
    mesasOcupadas: 8,
    mesasTotal: 12,
    tiempoPromedio: 23,

    ventasSemanales: {
        labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
        data: [3200, 2800, 4100, 3900, 5200, 8500, 7200]
    },

    platillosMasVendidos: [
        { nombre: 'Tacos al Pastor', categoria: 'Platillo Principal', ventas: 45, precio: 120 },
        { nombre: 'Hamburguesa Especial', categoria: 'Platillo Principal', ventas: 38, precio: 150 },
        { nombre: 'Enchiladas Suizas', categoria: 'Platillo Principal', ventas: 32, precio: 110 },
        { nombre: 'Pizza Margarita', categoria: 'Platillo Principal', ventas: 28, precio: 180 },
        { nombre: 'Ensalada César', categoria: 'Entradas', ventas: 24, precio: 95 }
    ],

    ordenesRecientes: [
        { id: '#0047', mesa: 5, mesero: 'Juan Pérez', total: 450.00, estado: 'completed', hora: '14:35' },
        { id: '#0046', mesa: 3, mesero: 'María López', total: 320.50, estado: 'preparing', hora: '14:28' },
        { id: '#0045', mesa: 8, mesero: 'Carlos Ruiz', total: 680.00, estado: 'pending', hora: '14:15' },
        { id: '#0044', mesa: 2, mesero: 'Ana García', total: 290.00, estado: 'completed', hora: '14:02' },
        { id: '#0043', mesa: 7, mesero: 'Juan Pérez', total: 540.00, estado: 'completed', hora: '13:45' }
    ]
};

// ========================================
// Inicialización
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    // ✅ Guard clause - solo ejecutar si estamos en dashboard
    if (!document.getElementById('ventasHoy')) {
        console.log('Dashboard module: Not in dashboard view');
        return;
    }

    console.log('Dashboard module initializing...');
    
    initDashboard();
    setupEventListeners();
    loadDashboardData();
    initSalesChart();
});

/**
 * Inicializa el dashboard con configuraciones básicas
 */
/**
 * Inicializa el dashboard con configuraciones básicas
 */
function initDashboard() {
    console.log('Dashboard module loaded');

    // ✅ Solo ejecutar si estamos en la vista de dashboard
    if (!document.getElementById('ventasHoy')) {
        console.log('Not in dashboard view, skipping initialization');
        return;
    }

    console.log('Dashboard inicializado');

    // Verificar si hay sesión activa (simulado)
    checkSession();

    // Actualizar fecha y hora actual
    updateDateTime();
    setInterval(updateDateTime, 60000); // Actualizar cada minuto
}

/**
 * Verifica si existe una sesión activa
 */
function checkSession() {
    // Aquí irá la lógica de verificación de sesión con PHP
    // Por ahora solo simulación
    const hasSession = true;

    if (!hasSession) {
        window.location.href = 'login.php';
    }
}

/**
 * Actualiza la fecha y hora en el dashboard
 */
function updateDateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('es-MX', {
        hour: '2-digit',
        minute: '2-digit'
    });

    // Aquí podrías agregar un elemento para mostrar la hora si lo deseas
    console.log('Hora actual:', timeString);
}

// ========================================
// Event Listeners
// ========================================
/**
 * Configura todos los event listeners del dashboard
 */
function setupEventListeners() {
    // Toggle sidebar
    toggleSidebarBtn.addEventListener('click', toggleSidebar);

    // Notificaciones
    notificationBtn.addEventListener('click', showNotifications);

    // Logout
    document.addEventListener('click', function(e) {
        if (e.target.closest('#logoutBtn')) {
            handleLogout(e);
        }
    });

    // Navegación del sidebar
    setupNavigation();

    // Cerrar sidebar al hacer click fuera (mobile)
    document.addEventListener('click', handleOutsideClick);
}

/**
 * Toggle del sidebar en dispositivos móviles
 */
function toggleSidebar() {
    sidebar.classList.toggle('active');
}

/**
 * Cierra el sidebar si se hace click fuera de él (mobile)
 * @param {Event} event - Evento de click
 */
function handleOutsideClick(event) {
    if (window.innerWidth <= 767) {
        if (!sidebar.contains(event.target) && !toggleSidebarBtn.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    }
}

/**
 * Muestra el panel de notificaciones
 */
function showNotifications() {
    // Aquí irá la lógica para mostrar notificaciones
    Toast.info('Sistema de notificaciones en desarrollo', 5000, 'Próximamente');
}

/**
 * Maneja el cierre de sesión
 */
function handleLogout(e) {
    e.preventDefault();

    Toast.confirm(
        '¿Estás seguro de que deseas cerrar sesión?',
        () => {
            // Confirmar logout
            Toast.info('Cerrando sesión...', 2000);
            setTimeout(() => {
                const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
                window.location.href = `${baseUrl}/auth/logout`;
            }, 2000);
        }
    );
}

/**
 * Configura la navegación del sidebar
 */
function setupNavigation() {
    // Delegación de eventos para navegación
    document.addEventListener('click', function(e) {
        const navLink = e.target.closest('.sidebar-nav .nav-link');
        
        if (navLink && navLink.getAttribute('href') === '#') {
            e.preventDefault();
            
            // Remover active de todos
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => {
                l.classList.remove('active');
            });

            // Agregar active al clickeado
            navLink.classList.add('active');

            if (window.innerWidth <= 767) {
                sidebar.classList.remove('active');
            }

            console.log('Navegando a:', navLink.querySelector('span').textContent);
        }
    });
}

// ========================================
// Carga de Datos
// ========================================
/**
 * Carga todos los datos del dashboard
 */
function loadDashboardData() {
    // ✅ Solo ejecutar si estamos en dashboard
    if (!document.getElementById('ventasHoy')) {
        return;
    }

    updateStatsCards();
    loadTopDishes();
    loadRecentOrders();
}

/**
 * Actualiza las tarjetas de estadísticas
 */
function updateStatsCards() {
    // Ventas Hoy
    const ventasHoyEl = document.getElementById('ventasHoy');
    ventasHoyEl.textContent = formatCurrency(dashboardData.ventasHoy);

    // Órdenes Hoy
    const ordenesHoyEl = document.getElementById('ordenesHoy');
    ordenesHoyEl.textContent = dashboardData.ordenesHoy;

    // Mesas Ocupadas
    const mesasOcupadasEl = document.getElementById('mesasOcupadas');
    mesasOcupadasEl.textContent = `${dashboardData.mesasOcupadas}/${dashboardData.mesasTotal}`;

    // Tiempo Promedio
    const tiempoPromedioEl = document.getElementById('tiempoPromedio');
    tiempoPromedioEl.textContent = `${dashboardData.tiempoPromedio} min`;
}

/**
 * Carga la lista de platillos más vendidos
 */
function loadTopDishes() {
    const container = document.getElementById('topDishes');
    container.innerHTML = '';

    dashboardData.platillosMasVendidos.forEach((platillo, index) => {
        const dishItem = createDishItem(platillo, index + 1);
        container.appendChild(dishItem);
    });
}

/**
 * Crea un elemento de platillo
 * @param {Object} platillo - Datos del platillo
 * @param {number} rank - Posición en el ranking
 * @returns {HTMLElement} Elemento del platillo
 */
function createDishItem(platillo, rank) {
    const div = document.createElement('div');
    div.className = 'dish-item';

    const totalVentas = platillo.ventas * platillo.precio;

    div.innerHTML = `
        <div class="dish-info">
            <div class="dish-rank">${rank}</div>
            <div class="dish-details">
                <h6>${platillo.nombre}</h6>
                <span>${platillo.categoria}</span>
            </div>
        </div>
        <div class="dish-sales">
            <strong>${formatCurrency(totalVentas)}</strong>
            <small>${platillo.ventas} vendidos</small>
        </div>
    `;

    return div;
}

/**
 * Carga las órdenes recientes en la tabla
 */
function loadRecentOrders() {
    const tbody = document.getElementById('recentOrders');
    tbody.innerHTML = '';

    dashboardData.ordenesRecientes.forEach(orden => {
        const row = createOrderRow(orden);
        tbody.appendChild(row);
    });
}

/**
 * Crea una fila de orden
 * @param {Object} orden - Datos de la orden
 * @returns {HTMLElement} Fila de la tabla
 */
function createOrderRow(orden) {
    const tr = document.createElement('tr');

    const statusText = getStatusText(orden.estado);
    const statusClass = orden.estado;

    tr.innerHTML = `
        <td><strong>${orden.id}</strong></td>
        <td>Mesa ${orden.mesa}</td>
        <td>${orden.mesero}</td>
        <td><strong>${formatCurrency(orden.total)}</strong></td>
        <td><span class="badge-status ${statusClass}">${statusText}</span></td>
        <td>${orden.hora}</td>
        <td>
            <button class="btn-icon" onclick="viewOrderDetails('${orden.id}')">
                <i class="fas fa-eye"></i>
            </button>
        </td>
    `;

    return tr;
}

/**
 * Obtiene el texto del estado en español
 * @param {string} status - Estado de la orden
 * @returns {string} Texto del estado
 */
function getStatusText(status) {
    const statusMap = {
        'pending': 'Pendiente',
        'preparing': 'Preparando',
        'completed': 'Completada'
    };

    return statusMap[status] || status;
}

/**
 * Ver detalles de una orden
 * @param {string} orderId - ID de la orden
 */
function viewOrderDetails(orderId) {
    console.log('Ver detalles de orden:', orderId);
    Toast.info(`Cargando detalles de la orden ${orderId}...`, 3000);
}

// ========================================
// Gráfica de Ventas
// ========================================
/**
 * Inicializa la gráfica de ventas semanales
 */
function initSalesChart() {
    const ctx = document.getElementById('salesChart');

    if (!ctx) {
        console.error('Canvas de gráfica no encontrado');
        return;
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dashboardData.ventasSemanales.labels,
            datasets: [{
                label: 'Ventas ($)',
                data: dashboardData.ventasSemanales.data,
                borderColor: '#e67e22',
                backgroundColor: 'rgba(230, 126, 34, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#e67e22',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#2c3e50',
                    padding: 12,
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function (context) {
                            return 'Ventas: ' + formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return '$' + value.toLocaleString('es-MX');
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// ========================================
// Utilidades
// ========================================
/**
 * Formatea un número como moneda mexicana
 * @param {number} amount - Cantidad a formatear
 * @returns {string} Cantidad formateada
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(amount);
}

/**
 * Actualiza los datos del dashboard desde el servidor
 * Esta función será llamada periódicamente
 */
async function fetchDashboardData() {
    try {
        // Aquí irá la petición fetch al backend PHP
        const response = await fetch('api/dashboard.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Error al obtener datos del dashboard');
        }

        const data = await response.json();

        // Actualizar datos
        Object.assign(dashboardData, data);
        loadDashboardData();

    } catch (error) {
        console.error('Error al cargar datos:', error);
        // Mostrar mensaje de error al usuario
    }
}

/**
 * Inicia actualización automática de datos
 * @param {number} interval - Intervalo en milisegundos (default: 30000 = 30 segundos)
 */
function startAutoRefresh(interval = 30000) {
    setInterval(() => {
        fetchDashboardData();
        console.log('Datos actualizados');
    }, interval);
}

// Iniciar auto-refresh (descomentado cuando el backend esté listo)
// startAutoRefresh();