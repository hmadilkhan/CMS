<?php

use App\Http\Controllers\AuroraController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InverterTypeController;
use App\Http\Controllers\LaborCostController;
use App\Http\Controllers\ModuleTypeController;
use App\Http\Controllers\OfficeCostController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ToolController;
use App\Livewire\AdminDashboard;
use App\Livewire\DynamicReport;
use App\Livewire\DynamicReportBuilder;
use App\Livewire\DynamicReport\DynamicReportForm;
use App\Livewire\ReportRunner;
use App\Models\InverterType;
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Controllers\ImpersonateController;

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

Route::get('/CRM', function () {
    return redirect('https://crm.solenenergyco.com');
});


Route::get('/storage-link', function () {
    $targetFolder = storage_path("app/public");
    $linkFolder = $_SERVER['DOCUMENT_ROOT'] . "/storage";
    symlink($targetFolder, $linkFolder);
});

Route::get('/', function () {
    // return view('welcome');
    return view('auth.login');
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

Route::post('store-ticket', [App\Http\Controllers\NewTicketController::class, 'store'])->name("store.ticket");
Route::get('check-website-project/{code}/{email}', [App\Http\Controllers\ProjectController::class, 'checkWebsiteProject'])->name('get.website.project');
Route::get('/track-your-project/{project_id}', [App\Http\Controllers\ProjectController::class, 'trackYourProject']);
Route::post('show-website-emails', [App\Http\Controllers\ImapController::class, 'showEmails'])->name("show.website.emails");

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'dashboard'])
        ->middleware('check.admin')
        ->name('dashboard');
    
    Route::get('/admin-dashboard', AdminDashboard::class)
        ->middleware('role:Super Admin')
        ->name('admin.dashboard');
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
    Route::resource('labor-costs', LaborCostController::class);

    Route::resource('tools', ToolController::class);
    Route::get('tools-index/{id?}', [App\Http\Controllers\ToolController::class, 'index'])->name('tools.index');
    Route::post('tools-delete', [App\Http\Controllers\ToolController::class, 'toolDelete'])->name('tools.delete');


    Route::post('get-employees-with-department', [App\Http\Controllers\EmployeeController::class, 'getDepartmentEmployees'])->name('get.employee.department');

    Route::post('get-finance-option-by-id', [App\Http\Controllers\CustomerController::class, 'getFinanceOptionById'])->name('get.finance.option.by.id');
    Route::post('get-loan-terms', [App\Http\Controllers\CustomerController::class, 'getLoanTerms'])->name('get.loan.terms');
    Route::post('get-loan-aprs', [App\Http\Controllers\CustomerController::class, 'getLoanAprs'])->name('get.loan.aprs');
    Route::post('get-dealer-fee', [App\Http\Controllers\CustomerController::class, 'getDealerFee'])->name('get.dealer.fee');
    Route::post('get-redline-cost', [App\Http\Controllers\CustomerController::class, 'getRedlineCost'])->name('get.redline.cost');
    Route::post('get-sub-adders', [App\Http\Controllers\CustomerController::class, 'getSubAdders'])->name('get.sub.adders');
    Route::post('get-adders', [App\Http\Controllers\CustomerController::class, 'getAdderDetails'])->name('get.adders');
    Route::post('get-module-types', [App\Http\Controllers\CustomerController::class, 'getModulTypevalue'])->name('get.module.types');
    Route::post('delete-customer', [App\Http\Controllers\CustomerController::class, 'destroy'])->name('delete.customer');
    Route::post('get-sales-partner-users', [App\Http\Controllers\CustomerController::class, 'getSalesPartnerUsers'])->name('get.salespartnets.users');

    Route::post('send-email', [App\Http\Controllers\CustomerController::class, 'sendEmail'])->name("send.email");

    Route::post('project-list', [App\Http\Controllers\ProjectController::class, 'getProjectList'])->name('projects.list');
    Route::post('get-sub-departments', [App\Http\Controllers\ProjectController::class, 'getSubDepartments'])->name('get.sub.departments');
    Route::post('projects-move', [App\Http\Controllers\ProjectController::class, 'projectMove'])->name('projects.move'); // OLD Should be remove in Future
    Route::post('move-project', [App\Http\Controllers\ProjectController::class, 'moveProject'])->name('move.project'); // NEW 
    Route::post('project-call-logs', [App\Http\Controllers\ProjectController::class, 'saveCallLogs'])->name('projects.call.logs');
    Route::post('project-call-script', [App\Http\Controllers\ProjectController::class, 'getCallScript'])->name('projects.call.script');
    Route::post('project-email-script', [App\Http\Controllers\ProjectController::class, 'getEmailScript'])->name('projects.email.script');
    Route::post('save-project-files', [App\Http\Controllers\ProjectController::class, 'saveProjectFiles'])->name('projects.files');
    Route::post('projects-adders', [App\Http\Controllers\ProjectController::class, 'projectAdders'])->name('projects.adders');
    Route::post('projects-assign-to-employee', [App\Http\Controllers\ProjectController::class, 'assignTaskToEmployee'])->name('projects.assign');
    Route::post('projects-status', [App\Http\Controllers\ProjectController::class, 'projectStatus'])->name('projects.status');
    Route::get('projects-list', [App\Http\Controllers\ProjectController::class, 'getProjects'])->name('projects');
    Route::post('get-departments-fields', [App\Http\Controllers\ProjectController::class, 'getDepartmentFields'])->name('get.departments.fields');
    Route::post('save-department-notes', [App\Http\Controllers\ProjectController::class, 'saveDepartmentNotes'])->name('department.notes');
    Route::post('delete-file', [App\Http\Controllers\ProjectController::class, 'deleteFile'])->name('delete.file');
    Route::post('project-accept-file', [App\Http\Controllers\ProjectController::class, 'projectAcceptance'])->name('project.accept.file');
    Route::get('/pdf/{id}', [App\Http\Controllers\ProjectController::class, 'generatePDF'])->name('generate.pdf.file');
    Route::post('action-project-acceptance', [App\Http\Controllers\ProjectController::class, 'actionProjectAcceptance'])->name('action.project.acceptance');
    Route::get('/projects/{id}/{ghost?}', [App\Http\Controllers\ProjectController::class, 'show'])->name('projects.show');

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


        // UTILITY COMPANY 
        Route::get('/view-utility-company/{id?}', 'utilityCompanyView')->name("view.utility.types");
        Route::post('/utility-company-store', 'utilityCompanyStore')->name("utility.type.store");
        Route::post('/utility-company-update', 'utilityCompanyUpdate')->name("utility.type.update");
        Route::post('/utility-company-delete', 'utilityCompanyDelete')->name("utility.type.delete");

        // FINANCE OPTION VIEW
        Route::get('/view-finance-option/{id?}', 'financeOptionView')->name("finance.option.types");
        Route::post('/finance-option-store', 'financeOptionStore')->name("finance.option.store");
        Route::post('/finance-option-update', 'financeOptionUpdate')->name("finance.option.update");
        Route::post('/finance-option-delete', 'financeOptionDelete')->name("finance.option.delete");

        // SALES PARTNERS VIEW
        Route::get('/sales-partner-type/{id?}', 'salesPartnerView')->name("sales.partner.types");
        Route::post('/sales-partner-store', 'salesPartnerStore')->name("sales.partner.store");
        Route::post('/sales-partner-update', 'salesPartnerUpdate')->name("sales.partner.update");
        Route::post('/sales-partner-delete', 'salesPartnerDelete')->name("sales.partner.delete");
        Route::post('/sales-partner-overwrite-prices', 'salesPartnerOverwriteCost')->name("sales.partner.overwrite.prices");

        // CALL SCRIPTS
        Route::get('/view-call-scripts/{id?}', 'callScriptList')->name("call.scripts.list");
        Route::post('/call-scripts-store', 'callScriptStore')->name("call.scripts.store");
        Route::post('/call-scripts-update', 'callScriptUpdate')->name("call.scripts.update");
        Route::post('/call-scripts-delete', 'callScriptDelete')->name("call.scripts.delete");

        // Email SCRIPTS
        Route::get('/view-email-scripts/{id?}', 'emailScriptList')->name("email.scripts.list");
        Route::post('/email-scripts-store', 'emailScriptStore')->name("email.scripts.store");
        Route::post('/email-scripts-update', 'emailScriptUpdate')->name("email.scripts.update");
        Route::post('/email-scripts-delete', 'emailScriptDelete')->name("email.scripts.delete");

        // LOAN TERM VIEW
        Route::get('/view-loan-term/{id?}', 'loanTermView')->name("loan.term");
        Route::post('/loan-term-store', 'loanTermStore')->name("loan.term.store");
        Route::post('/loan-term-update', 'loanTermUpdate')->name("loan.term.update");
        Route::post('/loan-term-delete', 'loanTermDelete')->name("loan.term.delete");
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
        Route::get('/profitable-report-excel-export/{from}/{to}', 'getProfitableReportExport')->name("profitable.report.excel.export");
        Route::get('/profitable-report-pdf-export/{from}/{to}', 'getProfitableReportPdfExport')->name("profitable.report.pdf.export");

        // FORECAST REPORT
        Route::get('/forecast-report', 'forecastReport')->name("forecast.report");
        Route::post('/forecast-report', 'getForecastReport')->name("forecast.report");
        Route::get('/forecast-report-excel-export/{from}/{to}', 'getForecastReportExport')->name("forecast.report.excel.export");
        Route::get('/forecast-report-pdf-export/{from}/{to}', 'getForecastReportPdfExport')->name("forecast.report.pdf.export");

        // OVERRIDE REPORT
        Route::get('/override-report', 'overrideReport')->name("override.report");
        Route::post('/override-report', 'getOverrideReport')->name("override.report");
        Route::get('/override-report-excel-export/{salespartner}/{from}/{to}', 'getOverrideReportExport')->name("override.report.excel.export");
        Route::get('/override-report-pdf-export/{salespartner}/{from}/{to}', 'getOverrideReportPdfExport')->name("override.report.pdf.export");

        //  Route::get("dynamic-report","DynamicReport");
        Route::get("dynamic-report", DynamicReport::class);
        Route::get("report-builder", DynamicReportBuilder::class)->name("report-builder");
        Route::get("report-runner",ReportRunner::class)->name("report-runner");
        Route::get("dynamic-report-builder", "DynamicReportBuilder")->name("dynamic.report.builder");
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

    // IMAP SETUP
    Route::get('fetch-emails', [App\Http\Controllers\ImapController::class, 'fetchEmails']);
    Route::post('show-emails', [App\Http\Controllers\ImapController::class, 'showEmails'])->name("show.emails");
    Route::post('fetch-emails', [App\Http\Controllers\ImapController::class, 'fetchDepartmentMails'])->name("fetch.emails");
});

// Start impersonation — only Super Admins
Route::middleware(['web','auth','role:Super Admin'])
    ->get('/impersonate/take/{id}', [ImpersonateController::class, 'take'])
    ->name('impersonate');

// Stop impersonation — available only when impersonating
Route::middleware(['web','auth'])->group(function () {
    Route::impersonate(); // creates 'impersonate' & 'impersonate.leave'
});

require __DIR__ . '/auth.php';
