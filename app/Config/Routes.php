<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');

// Auth
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attempt');
$routes->get('logout', 'AuthController::logout');


// Protect everything under admin
$routes->group('admin', ['filter' => 'auth'], function($routes){

    $routes->get('/', 'Admin\DashboardController::index');
    $routes->get('dashboard', 'Admin\DashboardController::index');

    // SETTINGS
    $routes->group('settings', function($routes){

        $routes->get('modules', 'Admin\ModulesController::index');
        $routes->post('modules/upload', 'Admin\ModulesController::upload');
        $routes->post('modules/enable/(:segment)', 'Admin\ModulesController::enable/$1');
        $routes->post('modules/disable/(:segment)', 'Admin\ModulesController::disable/$1');
        $routes->post('modules/delete/(:segment)', 'Admin\ModulesController::delete/$1');

        $routes->get('locations', 'Admin\LocationsController::index');
        $routes->get('locations/list', 'Admin\LocationsController::list'); // ?level=1&parent=PCODE
        $routes->post('locations/toggle/(:segment)', 'Admin\LocationsController::toggle/$1');
        $routes->post('locations/rename/(:segment)', 'Admin\LocationsController::rename/$1');

        $routes->get('users', 'Admin\UsersController::index');
        $routes->get('users/create', 'Admin\UsersController::create');
        $routes->post('users', 'Admin\UsersController::store');
        $routes->get('users/(:num)/edit', 'Admin\UsersController::edit/$1');
        $routes->post('users/(:num)', 'Admin\UsersController::update/$1');
        $routes->post('users/(:num)/toggle', 'Admin\UsersController::toggle/$1');
    });


    // REGISTRY
    $routes->group('registry', function($routes){

        // Household Profiling
        $routes->get('household-profiling', 'Admin\HouseholdProfilingController::index');
        $routes->get('household-profiling/create', 'Admin\HouseholdProfilingController::create');
        $routes->post('household-profiling', 'Admin\HouseholdProfilingController::store');

        $routes->get('household-profiling/(:num)/edit', 'Admin\HouseholdProfilingController::edit/$1');
        $routes->post('household-profiling/(:num)', 'Admin\HouseholdProfilingController::update/$1');
        $routes->post('household-profiling/(:num)/delete', 'Admin\HouseholdProfilingController::delete/$1');

        $routes->get('household-profiling/(:num)', 'Admin\HouseholdProfilingController::show/$1');

        // AJAX search for linking members
        $routes->get('household-profiling/search-members', 'Admin\HouseholdProfilingController::searchMembers');

         // Medical history AJAX
        $routes->get('household-profiling/member/(:num)/medical-histories', 'Admin\HouseholdProfilingController::medicalHistories/$1');
        $routes->post('household-profiling/member/(:num)/medical-histories/save', 'Admin\HouseholdProfilingController::saveMedicalHistory/$1');
        $routes->post('household-profiling/medical-history/(:num)/delete', 'Admin\HouseholdProfilingController::deleteMedicalHistory/$1');

        // Household Map Page
        $routes->get('household-map', 'Admin\HouseholdMapController::index');
    });

});


// MODULE SYSTEM
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