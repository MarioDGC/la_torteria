<?php
/**
 * Controlador del Dashboard
 * Requiere autenticación
 */

class DashboardController {

    /**
     * Vista principal del dashboard
     * Solo accesible para usuarios autenticados
     */
    public function index() {
        AuthMiddleware::handle();
    
        ob_start();
        include APP_PATH . '/views/dashboard/dashboard-index.php';
        $content = ob_get_clean();
    
        $this->render('layouts/dashboard', [
            'content' => $content,
            'pageTitle' => 'Dashboard',
            'currentView' => 'dashboard',
            'useCharts' => false, // Activar Chart.js
            'customJS' => ['dashboard'] // JS adicional opcional
        ]);
    }

    /**
    * Método helper para renderizar vistas con layout
    */
    private function render($layout, $data = []) {
        extract($data);
        include APP_PATH . '/views/' . $layout . '.php';
    }
}