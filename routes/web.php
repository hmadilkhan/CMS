<?php

use App\Http\Controllers\AuroraController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InverterTypeController;
use App\Http\Controllers\ModuleTypeController;
use App\Http\Controllers\OfficeCostController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ToolController;
use App\Models\InverterType;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/storage-link', function () {
    $targetFolder = storage_path("app/public");
    $linkFolder = $_SERVER['DOCUMENT_ROOT'] . "/storage";
    symlink($targetFolder, $linkFolder);
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/contact', function () {
    return view('contact');
});

Route::get('/about', function () {
    return view('about');
});

Route::get('/track-your-project', function () {
    return view('track-your-project');
});

Route::get('/get-track-project', [App\Http\Controllers\ProjectController::class, 'getWebsiteProject']);


Route::post('store-ticket', [App\Http\Controllers\NewTicketController::class, 'store'])->name("store.ticket");
Route::post('get-website-project', [App\Http\Controllers\ProjectController::class, 'getWebsiteProject'])->name('get.website.project');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'dashboard'])->name('dashboard');
    /* ADMIN ROUTES */
    Route::group(['middleware' => ['role:Super Admin']], function () {
        Route::get('register/{id?}', [App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])->name('get.register');
        Route::post('store-user', [App\Http\Controllers\Auth\RegisteredUserController::class, 'store'])->name("store.register");
        Route::post('update-user', [App\Http\Controllers\Auth\RegisteredUserController::class, 'update'])->name("update.user");
        Route::post('delete-user', [App\Http\Controllers\Auth\RegisteredUserController::class, 'delete'])->name("delete.user");

        Route::get('role/{id?}', [App\Http\Controllers\RoleController::class, 'index'])->name('role');
        Route::post('save-role', [App\Http\Controllers\RoleController::class, 'store'])->name('save.role');
        Route::post('update-role', [App\Http\Controllers\RoleController::class, 'update'])->name('update.role');
        Route::post('delete-role', [App\Http\Controllers\RoleController::class, 'delete'])->name('delete.role');

        Route::get('permission/{id?}', [App\Http\Controllers\PermissionController::class, 'index'])->name('permission');
        Route::post('save-permission', [App\Http\Controllers\PermissionController::class, 'store'])->name('permission.store');
        Route::post('update-permission', [App\Http\Controllers\PermissionController::class, 'update'])->name('update.permission');
        Route::post('delete-permission', [App\Http\Controllers\PermissionController::class, 'delete'])->name('permission.delete');

        Route::get('role-permission/{id?}', [App\Http\Controllers\PermissionController::class, 'rolePermission'])->name('role.permission');
        Route::post('store-permission', [App\Http\Controllers\PermissionController::class, 'storeRolePermission'])->name('store.permission');
        Route::post('update-role-permission', [App\Http\Controllers\PermissionController::class, 'updateRolePermission'])->name('update.role.permission');
        Route::post('delete-role-permission', [App\Http\Controllers\PermissionController::class, 'deleteRolePermission'])->name('delete.role.permission');

        Route::get('user-permission/{id?}', [App\Http\Controllers\PermissionController::class, 'userPermission'])->name('user.permission');
        Route::post('store-user-permission', [App\Http\Controllers\PermissionController::class, 'storeUserPermission'])->name('store.user.permission');
        Route::post('update-user-permission', [App\Http\Controllers\PermissionController::class, 'updateUserPermission'])->name('update.user.permission');
        Route::post('delete-user-permission', [App\Http\Controllers\PermissionController::class, 'deleteUserPermission'])->name('delete.user.permission');

        Route::resource('tasks', TaskController::class);
    });

    Route::resource('employees', EmployeeController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('projects', ProjectController::class);
    Route::resource('module-types', ModuleTypeController::class);
    Route::resource('office-costs', OfficeCostController::class);

    Route::resource('tools', ToolController::class);
    Route::get('tools-index/{id?}', [App\Http\Controllers\ToolController::class, 'index'])->name('tools.index');
    Route::post('tools-delete', [App\Http\Controllers\ToolController::class, 'toolDelete'])->name('tools.delete');


    Route::post('get-employees-with-department', [App\Http\Controllers\EmployeeController::class, 'getDepartmentEmployees'])->name('get.employee.department');

    Route::post('get-loan-terms', [App\Http\Controllers\CustomerController::class, 'getLoanTerms'])->name('get.loan.terms');
    Route::post('get-loan-aprs', [App\Http\Controllers\CustomerController::class, 'getLoanAprs'])->name('get.loan.aprs');
    Route::post('get-dealer-fee', [App\Http\Controllers\CustomerController::class, 'getDealerFee'])->name('get.dealer.fee');
    Route::post('get-redline-cost', [App\Http\Controllers\CustomerController::class, 'getRedlineCost'])->name('get.redline.cost');
    Route::post('get-sub-adders', [App\Http\Controllers\CustomerController::class, 'getSubAdders'])->name('get.sub.adders');
    Route::post('get-adders', [App\Http\Controllers\CustomerController::class, 'getAdderDetails'])->name('get.adders');
    Route::post('get-module-types', [App\Http\Controllers\CustomerController::class, 'getModulTypevalue'])->name('get.module.types');
    Route::post('delete-customer', [App\Http\Controllers\CustomerController::class, 'destroy'])->name('delete.customer');
    Route::post('get-sales-partner-users', [App\Http\Controllers\CustomerController::class, 'getSalesPartnerUsers'])->name('get.salespartnets.users');

    Route::post('project-list', [App\Http\Controllers\ProjectController::class, 'getProjectList'])->name('projects.list');
    Route::post('get-sub-departments', [App\Http\Controllers\ProjectController::class, 'getSubDepartments'])->name('get.sub.departments');
    Route::post('projects-move', [App\Http\Controllers\ProjectController::class, 'projectMove'])->name('projects.move');
    Route::post('project-call-logs', [App\Http\Controllers\ProjectController::class, 'saveCallLogs'])->name('projects.call.logs');
    Route::post('save-project-files', [App\Http\Controllers\ProjectController::class, 'saveProjectFiles'])->name('projects.files');
    Route::post('projects-adders', [App\Http\Controllers\ProjectController::class, 'projectAdders'])->name('projects.adders');
    Route::post('projects-assign-to-employee', [App\Http\Controllers\ProjectController::class, 'assignTaskToEmployee'])->name('projects.assign');
    Route::post('projects-status', [App\Http\Controllers\ProjectController::class, 'projectStatus'])->name('projects.status');
    Route::get('projects-list', [App\Http\Controllers\ProjectController::class, 'getProjects'])->name('projects');
    Route::post('get-departments-fields', [App\Http\Controllers\ProjectController::class, 'getDepartmentFields'])->name('get.departments.fields');
    Route::post('save-department-notes', [App\Http\Controllers\ProjectController::class, 'saveDepartmentNotes'])->name('department.notes');

    // ADDERS CONTROLLER
    Route::post('adders-store', [App\Http\Controllers\AdderController::class, 'store'])->name('adders.store');
    Route::post('adders-update', [App\Http\Controllers\AdderController::class, 'update'])->name('adders.update');
    Route::post('adders-remove', [App\Http\Controllers\AdderController::class, 'destroy'])->name('adders.remove');

    Route::controller(OperationController::class)->group(function () {
        // REDLINE COST
        Route::get('/view-redline-cost/{id?}', 'changeRedlineCostView')->name("view-redline-cost");
        Route::post('/get-redlines-cost', 'getRedlineCostByInverter')->name("get-redline-cost");
        Route::post('/redlinecost-store', 'redlineStore')->name("redlinecost.store");
        Route::post('/redlinecost-update', 'redlineUpdate')->name("redlinecost.update");
        Route::post('/redlinecost-delete', 'redlineDelete')->name("redlinecost.delete");

        // DEALER FEE
        Route::get('/view-dealer-fee/{id?}', 'dealerFeeView')->name("view-dealer-fee");
        Route::post('/dealerfee-store', 'dealerFeeStore')->name("dealerfee.store");
        Route::post('/dealerfee-update', 'dealerFeeUpdate')->name("dealerfee.update");
        Route::post('/dealerfee-delete', 'dealerFeeDelete')->name("dealerfee.delete");
        Route::post('/get-fianance-option', 'getFinanceOption')->name("finance.option");

        // ADDERS VIEW
        Route::get('/view-adder/{id?}', 'addersView')->name("view-adders");
        Route::post('/adder-store', 'addersStore')->name("adder.store");
        Route::post('/adders-update', 'addersUpdate')->name("adders.update");
        Route::post('/adders-delete', 'addersDelete')->name("adders.delete");
        Route::post('/get-sub-types', 'getSubTypes')->name("get.sub.types");

        // ADDERS TYPE VIEW
        Route::get('/view-adder-type/{id?}', 'addersTypeView')->name("view.adder.types");
        Route::post('/adders-type-store', 'addersTypeStore')->name("adder.type.store");
        Route::post('/adders-type-update', 'addersTypeUpdate')->name("adder.type.update");
        Route::post('/adders-type-delete', 'addersTypeDelete')->name("adder.type.delete");

        // SALES PARTNERS VIEW
        Route::get('/sales-partner-type/{id?}', 'salesPartnerView')->name("sales.partner.types");
        Route::post('/sales-partner-store', 'salesPartnerStore')->name("sales.partner.store");
        Route::post('/sales-partner-update', 'salesPartnerUpdate')->name("sales.partner.update");
        Route::post('/sales-partner-delete', 'salesPartnerDelete')->name("sales.partner.delete");
    });

    Route::controller(InverterTypeController::class)->group(function () {
        Route::get('/view-inverter-type/{id?}', 'inverterTypeIndex')->name("view-inverter-type");
        Route::post('/inverter-type-store', 'inverterTypeStore')->name("inverter.type.store");
        Route::post('/inverter-type-update', 'inverterTypeUpdate')->name("inverter.type.update");
        Route::post('/inverter-type-delete', 'inverterTypeDelete')->name("inverter.type.delete");
    });

    Route::controller(ReportController::class)->group(function () {
        Route::get('/reports-profilt', 'profitabilityReport')->name("reports.profit");
        Route::post('/reports-profilt', 'getProfitabilityReport')->name("reports.profit");
    });

    Route::controller(AuroraController::class)->group(function () {
        Route::get('/get-aurora-projects', 'index')->name("aurora.projects");
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/logout', [ProfileController::class, 'logout'])->name('profile.logout');

    Route::get('tickets', [App\Http\Controllers\NewTicketController::class, 'index'])->name("tickets");
    Route::post('change-ticket-status', [App\Http\Controllers\NewTicketController::class, 'changeStatus'])->name("change.ticket.status");
});

require __DIR__ . '/auth.php';
