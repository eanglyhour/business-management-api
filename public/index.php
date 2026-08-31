<?php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../app/controllers/PaymentController.php';
require_once __DIR__ . '/../vendor/autoload.php'; 
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(
    dirname(__DIR__)
);

$dotenv->load();

$database = new Database();
$db = $database->connect();
$router = new Router();

$database = new Database();
$db = $database->connect();
$router = new Router();

$router->post(
    '/api/auth/login',
    [AuthController::class, 'login']
);

$router->post(
    '/api/auth/refresh',
    [AuthController::class, 'refresh']
);

$router->post(
    '/api/auth/logout',
    [AuthController::class, 'logout']
);

$router->get(
    '/api/auth/me',
    [AuthController::class, 'me'],
    ['auth']
);

$router->get(
    '/api/roles',
    [RoleController::class, 'index'],
    ['auth', 'permission:role.view']
);

$router->get(
    '/api/roles/{id}',
    [RoleController::class, 'show'],
    ['auth', 'permission:role.view']
);

$router->post(
    '/api/roles',
    [RoleController::class, 'store'],
    ['auth', 'permission:role.create']
);

$router->put(
    '/api/roles/{id}',
    [RoleController::class, 'update'],
    ['auth', 'permission:role.update']
);

$router->delete(
    '/api/roles/{id}',
    [RoleController::class, 'destroy'],
    ['auth', 'permission:role.delete']
);

$router->get(
    '/api/categories',
    [CategoryController::class, 'index'],
    ['auth', 'permission:category.view']
);

$router->post(
    '/api/categories',
    [CategoryController::class, 'store'],
    ['auth', 'permission:category.create']
);

$router->get(
    '/api/categories/{id}',
    [CategoryController::class, 'show'],
    ['auth', 'permission:category.view']
);

$router->put(
    '/api/categories/{id}',
    [CategoryController::class, 'update'],
    ['auth', 'permission:category.update']
);

$router->delete(
    '/api/categories/{id}',
    [CategoryController::class, 'destroy'],
    ['auth', 'permission:category.delete']
);

$router->get(
    '/api/products',
    [ProductController::class, 'index'],
    ['auth', 'permission:product.view']
);

$router->post(
    '/api/products',
    [ProductController::class, 'store'],
    ['auth', 'permission:product.create']
);

$router->get(
    '/api/products/{id}',
    [ProductController::class, 'show'],
    ['auth', 'permission:product.view']
);

$router->put(
    '/api/products/{id}',
    [ProductController::class, 'update'],
    ['auth', 'permission:product.update']
);

$router->delete(
    '/api/products/{id}',
    [ProductController::class, 'destroy'],
    ['auth', 'permission:product.delete']
);

$router->post(
    '/api/products/{id}/images',
    [ProductController::class, 'uploadImages'],
    ['auth', 'permission:product.update']
);

$router->delete(
    '/api/products/images/{imageId}',
    [ProductController::class, 'deleteImage'],
    ['auth', 'permission:product.update']
);

$router->get(
    '/api/permissions',
    [PermissionController::class, 'index'],
    ['auth', 'permission:permission.view']
);

$router->get(
    '/api/permissions/{id}',
    [PermissionController::class, 'show'],
    ['auth', 'permission:permission.view']
);

$router->post(
    '/api/permissions',
    [PermissionController::class, 'store'],
    ['auth', 'permission:permission.create']
);

$router->put(
    '/api/permissions/{id}',
    [PermissionController::class, 'update'],
    ['auth', 'permission:permission.update']
);

$router->delete(
    '/api/permissions/{id}',
    [PermissionController::class, 'destroy'],
    ['auth', 'permission:permission.delete']
);

$router->get(
    '/api/roles/{roleId}/permissions',
    [RolePermissionController::class, 'index'],
    ['auth', 'permission:role.view']
);

$router->post(
    '/api/roles/{roleId}/permissions',
    [RolePermissionController::class, 'assignMultiple'],
    ['auth', 'permission:role.update']
);

$router->delete(
    '/api/roles/{roleId}/permissions/{permissionId}',
    [RolePermissionController::class, 'remove'],
    ['auth', 'permission:role.update']
);

$router->get(
    '/api/supply-locations',
    [SupplyLocationController::class, 'index'],
    ['auth', 'permission:supply_location.view']
);

$router->get(
    '/api/supply-locations/{id}',
    [SupplyLocationController::class, 'show'],
    ['auth', 'permission:supply_location.view']
);

$router->post(
    '/api/supply-locations',
    [SupplyLocationController::class, 'store'],
    ['auth', 'permission:supply_location.create']
);

$router->put(
    '/api/supply-locations/{id}',
    [SupplyLocationController::class, 'update'],
    ['auth', 'permission:supply_location.update']
);

$router->delete(
    '/api/supply-locations/{id}',
    [SupplyLocationController::class, 'destroy'],
    ['auth', 'permission:supply_location.delete']
);

$router->get(
    '/api/imports',
    [ImportController::class, 'index'],
    ['auth', 'permission:import.view']
);

$router->get(
    '/api/imports/{id}',
    [ImportController::class, 'show'],
    ['auth', 'permission:import.view']
);

$router->post(
    '/api/imports',
    [ImportController::class, 'store'],
    ['auth', 'permission:import.create']
);

$router->put(
    '/api/imports/{id}',
    [ImportController::class, 'update'],
    ['auth', 'permission:import.update']
);

$router->delete(
    '/api/imports/{id}',
    [ImportController::class, 'destroy'],
    ['auth', 'permission:import.delete']
);

$router->post(
    '/api/imports/{id}/pay',
    [ImportController::class, 'pay'],
    ['auth', 'permission:import.pay']
);

$router->get(
    '/api/imports/{importId}/details',
    [ImportDetailController::class, 'index'],
    ['auth', 'permission:import.view']
);

$router->get(
    '/api/imports/{importId}/details/{id}',
    [ImportDetailController::class, 'show'],
    ['auth', 'permission:import.view']
);

$router->put(
    '/api/imports/{importId}/details/{id}',
    [ImportDetailController::class, 'update'],
    ['auth', 'permission:import.update']
);

$router->delete(
    '/api/imports/{importId}/details/{id}',
    [ImportDetailController::class, 'destroy'],
    ['auth', 'permission:import.delete']
);

$router->get(
    '/api/import-payments',
    [ImportPaymentController::class, 'index'],
    ['auth', 'permission:import_payment.view']
);

$router->get(
    '/api/import-payments/{id}',
    [ImportPaymentController::class, 'show'],
    ['auth', 'permission:import_payment.view']
);

$router->get(
    '/api/imports/{importId}/payments',
    [ImportPaymentController::class, 'getByImportId'],
    ['auth', 'permission:import_payment.view']
);

$router->get(
    '/api/imports/{importId}/payments/summary',
    [ImportPaymentController::class, 'summary'],
    ['auth', 'permission:import_payment.view']
);

$router->get(
    '/api/users',
    [UserController::class, 'index'],
    ['auth', 'permission:user.view']
);

$router->get(
    '/api/users/{id}',
    [UserController::class, 'show'],
    ['auth', 'permission:user.view']
);

$router->post(
    '/api/users',
    [UserController::class, 'store'],
    ['auth', 'permission:user.create']
);

$router->put(
    '/api/users/{id}',
    [UserController::class, 'update'],
    ['auth', 'permission:user.update']
);

$router->delete(
    '/api/users/{id}',
    [UserController::class, 'destroy'],
    ['auth', 'permission:user.delete']
);


$router->get(
    "/api/companies",
    [companyController::class, "index"]
);

// GET /api/companies/{id}
$router->get(
    "/api/companies/{id}",
    [companyController::class, "show"]
);

// POST /api/companies
$router->post(
    "/api/companies",
    [companyController::class, "store"]
);

// PUT /api/companies/{id}
$router->put(
    "/api/companies/{id}",
    [companyController::class, "update"]
);

// DELETE /api/companies/{id}
$router->delete(
    "/api/companies/{id}",
    [companyController::class, "destroy"]
);

//department

$router->get(
    '/api/departments',
    [DepartmentController::class, 'index']
);

$router->get(
    '/api/departments/{id}',
    [DepartmentController::class, 'show']
);

$router->post(
    '/api/departments',
    [DepartmentController::class, 'store']
);

$router->put(
    '/api/departments/{id}',
    [DepartmentController::class, 'update']
);

$router->delete(
    '/api/departments/{id}',
    [DepartmentController::class, 'destroy']
);
//employee_profiles

$router->get(
    '/api/employee-profiles',
    [EmployeeProfileController::class, 'index']
);

$router->get(
    '/api/employee-profiles/{id}',
    [EmployeeProfileController::class, 'show']
);

$router->post(
    '/api/employee-profiles',
    [EmployeeProfileController::class, 'store']
);

$router->put(
    '/api/employee-profiles/{id}',
    [EmployeeProfileController::class, 'update']
);

$router->delete(
    '/api/employee-profiles/{id}',
    [EmployeeProfileController::class, 'destroy']
);

//shift

$router->get(
    '/api/shifts',
    [ShiftController::class, 'index']
);

$router->get(
    '/api/shifts/{id}',
    [ShiftController::class, 'show']
);

$router->get(
    '/api/companies/{companyId}/shifts',
    [ShiftController::class, 'companyShifts']
);

$router->post(
    '/api/shifts',
    [ShiftController::class, 'store']
);

$router->put(
    '/api/shifts/{id}',
    [ShiftController::class, 'update']
);

$router->delete(
    '/api/shifts/{id}',
    [ShiftController::class, 'destroy']
);
//payment

$router->post(
    '/api/payments_bakong',
    [PaymentController_Bakong::class, 'create']
);


$router->post(
    '/api/payments_bakong/verify',
    [PaymentController_Bakong::class, 'verify']
);

//user_shifts
// GET /api/user-shifts
$router->get(
    "/api/user-shifts",
    [userShiftController::class, "index"]
);


// GET /api/user-shifts/{id}
$router->get(
    "/api/user-shifts/{id}",
    [userShiftController::class, "show"]
);


// GET /api/users/{userId}/shifts
$router->get(
    "/api/users/{userId}/shifts",
    [userShiftController::class, "userShifts"]
);


// GET /api/users/{userId}/current-shift
$router->get(
    "/api/users/{userId}/current-shift",
    [userShiftController::class, "currentShift"]
);


// POST /api/user-shifts
$router->post(
    "/api/user-shifts",
    [userShiftController::class, "store"]
);


// PUT /api/user-shifts/{id}
$router->put(
    "/api/user-shifts/{id}",
    [userShiftController::class, "update"]
);


// DELETE /api/user-shifts/{id}
$router->delete(
    "/api/user-shifts/{id}",
    [userShiftController::class, "destroy"]
);

//attendance
$router->get(
    '/api/attendance',
    [AttendanceController::class, 'index']
);

$router->get(
    '/api/attendance/{id}',
    [AttendanceController::class, 'show']
);

$router->get(
    '/api/attendance/user/{userId}',
    [AttendanceController::class, 'byUser']
);

$router->get(
    '/api/attendance/date/{date}',
    [AttendanceController::class, 'byDate']
);

$router->post(
    '/api/attendance',
    [AttendanceController::class, 'store']
);

$router->put(
    '/api/attendance/{id}',
    [AttendanceController::class, 'update']
);

$router->patch(
    '/api/attendance/{id}/checkout',
    [AttendanceController::class, 'checkout']
);

$router->delete(
    '/api/attendance/{id}',
    [AttendanceController::class, 'destroy']
);

//check_out

$router->get(
    '/api/attendance-checkouts',
    [AttendanceCheckoutController::class, 'index']
);

$router->get(
    '/api/attendance-checkouts/user/{userId}',
    [AttendanceCheckoutController::class, 'byUser']
);

$router->get(
    '/api/attendance-checkouts/{id}',
    [AttendanceCheckoutController::class, 'show']
);

$router->post(
    '/api/attendance-checkouts',
    [AttendanceCheckoutController::class, 'store']
);

$router->delete(
    '/api/attendance-checkouts/{id}',
    [AttendanceCheckoutController::class, 'destroy']
);

$router->dispatch($db);