<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('admin/dashboard', 'Admin\DashboardController::index');
$routes->get('admin', 'Admin\DashboardController::index');

$routes->group('admin/settings', function($routes){
    $routes->get('modules', 'Admin\ModulesController::index');
    $routes->post('modules/upload', 'Admin\ModulesController::upload');
    $routes->post('modules/enable/(:segment)', 'Admin\ModulesController::enable/$1');
    $routes->post('modules/disable/(:segment)', 'Admin\ModulesController::disable/$1');
    $routes->post('modules/delete/(:segment)', 'Admin\ModulesController::delete/$1');
});

$enabledFile = WRITEPATH.'modules/enabled.php';

if (is_file($enabledFile)) {

    $enabledModules = include $enabledFile;

    foreach ($enabledModules as $slug) {

        $routesPath = WRITEPATH."modules/{$slug}/Config/Routes.php";

        if (is_file($routesPath)) {
            require $routesPath;
        }
    }
}