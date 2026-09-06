<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectUpdateController;
use App\Http\Controllers\ClientSettingsController;
use App\Http\Controllers\ClientAccountController;
use App\Http\Controllers\ClientSignupController;
use App\Http\Controllers\EmployeeAccountController;
use App\Http\Controllers\FirstLoginController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ProjectMaterialController;
use App\Http\Controllers\MaterialUsageController;
use App\Http\Controllers\PortfolioItemController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FundController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\SupplierContactController;
use App\Http\Controllers\MonthlyExpenseController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\KpiDashboardController;
use App\Http\Controllers\QuotationRequestController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [AuthController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Client Self-Registration
Route::get('/signup', [ClientSignupController::class, 'show'])->name('signup');
Route::post('/signup', [ClientSignupController::class, 'store'])->name('signup.post');
Route::get('/signup/next-username', [ClientSignupController::class, 'nextUsername'])->name('signup.next_username');

// First-Login Credential Setup (client & employee, no profile.complete middleware here)
Route::get('/setup/credentials', [FirstLoginController::class, 'show'])->name('setup.credentials');
Route::post('/setup/credentials', [FirstLoginController::class, 'handle'])->name('setup.credentials.submit');

// Forgot Password
Route::get('/forgot-password',          [ForgotPasswordController::class, 'showEmailForm'])->name('password.request');
Route::post('/forgot-password',         [ForgotPasswordController::class, 'sendCode'])->name('password.email');
Route::get('/forgot-password/verify',   [ForgotPasswordController::class, 'showVerifyForm'])->name('password.verify');
Route::post('/forgot-password/verify',  [ForgotPasswordController::class, 'verifyCode'])->name('password.verify.post');
Route::post('/forgot-password/resend',  [ForgotPasswordController::class, 'resendCode'])->name('password.resend');
Route::get('/forgot-password/reset',    [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/forgot-password/reset',   [ForgotPasswordController::class, 'resetPassword'])->name('password.reset');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['role:admin', 'no.back'])->group(function () {

    // Dashboard & Pages
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/employees', [AdminController::class, 'employees'])->name('employees');
    Route::get('/project-materials', [ProjectMaterialController::class, 'adminIndex'])->name('project_materials');
    Route::get('/project-materials/{projectId}', [ProjectMaterialController::class, 'adminDetail'])->name('project_materials.detail');
    Route::post('/project-materials/{projectId}/materials', [ProjectMaterialController::class, 'store'])->name('project_materials.store');
    Route::post('/project-materials/{projectId}/purchases', [ProjectMaterialController::class, 'storePurchase'])->name('project_materials.store_purchase');
    Route::delete('/project-materials/{projectId}/purchases/{purchaseId}', [ProjectMaterialController::class, 'destroyPurchase'])->name('project_materials.destroy_purchase');
    Route::post('/project-materials/{projectId}/send-bom', [ProjectMaterialController::class, 'sendBOM'])->name('project_materials.send_bom');
    Route::post('/project-materials/{projectId}/labor', [ProjectMaterialController::class, 'storeLabor'])->name('project_materials.store_labor');
    Route::put('/project-materials/{projectId}/labor/{laborId}', [ProjectMaterialController::class, 'updateLabor'])->name('project_materials.update_labor');
    Route::patch('/project-materials/{projectId}/labor/{laborId}/archive', [ProjectMaterialController::class, 'archiveLabor'])->name('project_materials.archive_labor');
    Route::patch('/project-materials/{projectId}/estimated-days', [ProjectMaterialController::class, 'updateEstimatedDays'])->name('project_materials.update_estimated_days');

    Route::post('/material-requests/{id}/fund', [MaterialRequestController::class, 'fund'])->name('material_requests.fund');
    Route::post('/material-requests/{id}/rerequest', [MaterialRequestController::class, 'rerequest'])->name('material_requests.rerequest');

    Route::get('/material-usage', [MaterialUsageController::class, 'adminIndex'])->name('material_usage');
    Route::get('/material-usage/{projectId}', [MaterialUsageController::class, 'adminDetail'])->name('material_usage.detail');
    Route::post('/material-usage/{projectId}', [MaterialUsageController::class, 'store'])->name('material_usage.store');
    Route::post('/material-usage/{projectId}/purchases', [MaterialUsageController::class, 'storePurchase'])->name('material_usage.store_purchase');
    Route::delete('/material-usage/{projectId}/purchases/{purchaseId}', [MaterialUsageController::class, 'destroyPurchase'])->name('material_usage.destroy_purchase');
    Route::patch('/material-usage/{projectId}/{usageId}/archive', [MaterialUsageController::class, 'archive'])->name('material_usage.archive');

    Route::post('/supplier-contacts', [SupplierContactController::class, 'store'])->name('supplier_contacts.store');
    Route::put('/supplier-contacts/{id}', [SupplierContactController::class, 'update'])->name('supplier_contacts.update');
    Route::delete('/supplier-contacts/{id}', [SupplierContactController::class, 'destroy'])->name('supplier_contacts.destroy');

    // Landing Page Portfolio Items (CMS)
    Route::post('/portfolio-items', [PortfolioItemController::class, 'store'])->name('portfolio.store');
    Route::put('/portfolio-items/{id}', [PortfolioItemController::class, 'update'])->name('portfolio.update');
    Route::patch('/portfolio-items/{id}/archive', [PortfolioItemController::class, 'archive'])->name('portfolio.archive');
    Route::delete('/portfolio-items/{id}', [PortfolioItemController::class, 'destroy'])->name('portfolio.destroy');

    // Landing Page Client Reviews (moderation)
    Route::patch('/reviews/{id}/archive', [ReviewController::class, 'archive'])->name('reviews.archive');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages');
    Route::get('/messages/contacts', [MessageController::class, 'contacts'])->name('messages.contacts');
    Route::get('/messages/thread/{type}/{id}', [MessageController::class, 'thread'])->name('messages.thread');
    Route::post('/messages/send', [MessageController::class, 'send'])->name('messages.send');
    Route::post('/messages/typing', [MessageController::class, 'typing'])->name('messages.typing');
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount'])->name('messages.unread_count');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments');
    Route::post('/payments/setup', [PaymentController::class, 'setup'])->name('payments.setup');
    Route::get('/payments/client/{client}', [PaymentController::class, 'clientPayments'])->name('payments.client');
    Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{id}/record', [PaymentController::class, 'recordPayment'])->name('payments.record');
    Route::post('/payments/{id}/billing-statements', [PaymentController::class, 'storeBillingStatement'])->name('payments.billing_statements.store');
    Route::get('/payments/{id}/billing-statements/{statementId}', [PaymentController::class, 'showBillingStatement'])->name('payments.billing_statements.show');
    Route::get('/revolving-fund', [FundController::class, 'index'])->name('revolving_fund');
    Route::post('/revolving-fund/setup', [FundController::class, 'setupInitial'])->name('revolving_fund.setup_initial');
    Route::post('/revolving-fund/release', [FundController::class, 'release'])->name('revolving_fund.release');
    Route::post('/revolving-fund/replenish', [FundController::class, 'replenish'])->name('revolving_fund.replenish');
    Route::get('/projects', [AdminController::class, 'projects'])->name('projects');
    Route::get('/projects/client/{client}', [AdminController::class, 'projectsClient'])->name('projects.client');
    Route::get('/projects/client-groups', [AdminController::class, 'projectClientGroups'])->name('projects.client_groups');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::get('/clients', [AdminController::class, 'clients'])->name('clients');

    // Quotation Requests
    Route::get('/quotation-requests', [QuotationRequestController::class, 'adminIndex'])->name('quotation_requests');
    Route::patch('/quotation-requests/{id}/decline', [QuotationRequestController::class, 'decline'])->name('quotation_requests.decline');
    Route::post('/quotation-requests/{id}/send-quotation', [QuotationRequestController::class, 'sendQuotation'])->name('quotation_requests.send_quotation');
    Route::get('/quotation-requests/{id}/convert', [QuotationRequestController::class, 'convert'])->name('quotation_requests.convert');
    Route::get('/quotation-requests/{id}/prefill', [QuotationRequestController::class, 'prefillData'])->name('quotation_requests.prefill');

    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/reports/project/{id}', [AdminController::class, 'projectKpi'])->name('reports.project');
    Route::get('/revenue/weekly', [AdminController::class, 'weeklyRevenue'])->name('revenue.weekly');

    // KPI Dashboard (Scorecard / Performance Trend)
    Route::get('/kpi-dashboard', [KpiDashboardController::class, 'index'])->name('kpi_dashboard');
    Route::get('/kpi-dashboard/data', [KpiDashboardController::class, 'data'])->name('kpi_dashboard.data');
    Route::get('/kpi-dashboard/report-range', [KpiDashboardController::class, 'reportRange'])->name('kpi_dashboard.report_range');
    Route::post('/kpi-dashboard/targets/quarter', [KpiDashboardController::class, 'saveQuarterTargets'])->name('kpi_dashboard.save_quarter_targets');
    Route::post('/kpi-dashboard/targets/project', [KpiDashboardController::class, 'saveProjectTargets'])->name('kpi_dashboard.save_project_targets');

    // Project View & Workflow
    Route::get('/project-view/{id}', [ProjectController::class, 'adminView'])->name('project_view');
    Route::post('/project/{id}/advance-phase', [ProjectController::class, 'advancePhase'])->name('project.advance_phase');
    Route::post('/project/{id}/add-update', [ProjectController::class, 'addUpdate'])->name('project.add_update');
    Route::post('/project/{id}/request-update', [ProjectController::class, 'requestUpdate'])->name('project.request_update');

    // Approve: hits ProjectUpdateController@approve
    Route::post('/project-updates/{updateId}/approve', [ProjectUpdateController::class, 'approve'])->name('project-update.approve');

    // Request revision: hits ProjectController@requestRevision (single source of truth)
    Route::post('/project-updates/{updateId}/request-revision', [ProjectController::class, 'requestRevision'])->name('project-update.request-revision');

    // Get update details (for modal)
    Route::get('/project-updates/{updateId}', [ProjectUpdateController::class, 'show'])->name('project-update.show');

    // Pending updates (for dashboard)
    Route::get('/pending-updates/count', [ProjectUpdateController::class, 'getPendingCount'])->name('pending-updates.count');
    Route::get('/pending-updates/list', [ProjectUpdateController::class, 'getPendingUpdates'])->name('pending-updates.list');

    // API endpoints for AJAX
    Route::get('/projects/{id}/updates/api', [ProjectController::class, 'getUpdates'])->name('project.updates.api');
    Route::get('/projects/{id}/details/api', [ProjectController::class, 'getProjectDetails'])->name('project.details.api');

    // Attachment routes
    Route::post('/project-updates/{updateId}/attachments', [ProjectUpdateController::class, 'uploadAttachment'])->name('project-update.upload-attachment');
    Route::delete('/attachments/{attachmentId}', [ProjectUpdateController::class, 'deleteAttachment'])->name('attachment.delete');

    // Project CRUD
    Route::post('/projects/store', [ProjectController::class, 'store'])->name('project.store');
    Route::put('/projects/{id}', [ProjectController::class, 'update'])->name('project.update');
    Route::patch('/projects/{id}/archive', [ProjectController::class, 'archive'])->name('project.archive');
    Route::post('/projects/{id}/assign-employees', [ProjectController::class, 'assignEmployees'])->name('project.assign_employees');

    // Project Templates (reusable tank specs)
    Route::delete('/project-templates/{id}', [ProjectController::class, 'destroyTemplate'])->name('project_templates.destroy');

    // Client Settings (CRUD)
    Route::post('/clients', [ClientSettingsController::class, 'store'])->name('client.store');
    Route::delete('/clients/{id}', [ClientSettingsController::class, 'destroy'])->name('client.destroy');
    Route::patch('/clients/{id}/archive', [ClientSettingsController::class, 'archive'])->name('client.archive');
    Route::patch('/clients/{id}/approve', [ClientSettingsController::class, 'approve'])->name('client.approve');
    Route::patch('/clients/{id}/reject', [ClientSettingsController::class, 'reject'])->name('client.reject');

    // Client List API
    Route::get('/clients/list', [ClientSettingsController::class, 'list'])->name('client.list');

    // Client Account
    Route::post('/client-account/store', [ClientAccountController::class, 'store'])->name('client-account.store');
    Route::get('/client-account/next-username', [ClientAccountController::class, 'nextUsername'])->name('client-account.next-username');

    // Employee Account
    Route::post('/employees', [EmployeeAccountController::class, 'store'])->name('employee-account.store');
    Route::put('/employees/{id}', [EmployeeAccountController::class, 'update'])->name('employee-account.update');
    Route::patch('/employees/{id}/archive', [EmployeeAccountController::class, 'archive'])->name('employee-account.archive');

    // Employee List API
    Route::get('/employees/list', [EmployeeAccountController::class, 'list'])->name('employee.list');

    // Salary Records
    Route::get('/monthly-expenses', [MonthlyExpenseController::class, 'index'])->name('monthly-expenses.index');
    Route::post('/monthly-expenses', [MonthlyExpenseController::class, 'store'])->name('monthly-expenses.store');
    Route::post('/monthly-expenses/allocate', [MonthlyExpenseController::class, 'allocate'])->name('monthly-expenses.allocate');
    Route::delete('/monthly-expenses/{id}', [MonthlyExpenseController::class, 'destroy'])->name('monthly-expenses.destroy');

    Route::get('/salary-records', [SalaryController::class, 'index'])->name('salary.index');
    Route::get('/salary-projects', [SalaryController::class, 'projects'])->name('salary.projects');
    Route::post('/salary-records', [SalaryController::class, 'store'])->name('salary.store');
    Route::put('/salary-records/{id}', [SalaryController::class, 'update'])->name('salary.update');
    Route::delete('/salary-records/{id}', [SalaryController::class, 'destroy'])->name('salary.destroy');

    // Profile, password & photo settings
    Route::put('/settings/profile',       [ProfileController::class, 'updateAdmin'])->name('settings.profile');
    Route::put('/settings/password',      [ProfileController::class, 'updateAdminPassword'])->name('settings.password');
    Route::post('/settings/photo',        [ProfileController::class, 'uploadAdminPhoto'])->name('settings.photo');
    Route::put('/settings/contact-info',  [AdminController::class, 'updateContactInfo'])->name('settings.contact_info');
    Route::put('/settings/kpi-targets',   [AdminController::class, 'updateKpiTargets'])->name('settings.kpi_targets');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'page'])->name('notifications');
    Route::get('/notifications/count', [NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

/*
|--------------------------------------------------------------------------
| Client Routes
|--------------------------------------------------------------------------
*/

Route::prefix('client')->name('client.')->middleware(['role:client', 'profile.complete', 'no.back'])->group(function () {
    Route::get('/dashboard', [ClientController::class, 'dashboard'])->name('dashboard');
    Route::get('/request-quotation', [QuotationRequestController::class, 'create'])->name('quotation.create');
    Route::post('/request-quotation', [QuotationRequestController::class, 'store'])->name('quotation.store');
    Route::get('/request-quotation/status', [QuotationRequestController::class, 'status'])->name('quotation.status');
    Route::post('/request-quotation/{id}/approve', [QuotationRequestController::class, 'approveQuotation'])->name('quotation.approve');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages');
    Route::get('/messages/contacts', [MessageController::class, 'contacts'])->name('messages.contacts');
    Route::get('/messages/thread/{type}/{id}', [MessageController::class, 'thread'])->name('messages.thread');
    Route::post('/messages/send', [MessageController::class, 'send'])->name('messages.send');
    Route::post('/messages/typing', [MessageController::class, 'typing'])->name('messages.typing');
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount'])->name('messages.unread_count');
    Route::get('/payments', [ClientController::class, 'payments'])->name('payments');
    Route::get('/payments/{id}', [PaymentController::class, 'clientShow'])->name('payments.show');
    Route::get('/payments/{id}/billing-statements/{statementId}', [PaymentController::class, 'clientShowBillingStatement'])->name('payments.billing_statements.show');
    Route::post('/payments/{id}/proof', [PaymentController::class, 'uploadProof'])->name('payments.proof.store');
    Route::get('/project-view/{id}', [ProjectController::class, 'clientView'])->name('project_view');
    Route::post('/project/{id}/shop-drawing/approve', [ProjectController::class, 'approveShopDrawing'])->name('project.shop_drawing.approve');
    Route::post('/project/{id}/shop-drawing/request-revision', [ProjectController::class, 'requestShopDrawingRevision'])->name('project.shop_drawing.request_revision');
    Route::get('/projects', [ClientController::class, 'projectList'])->name('projects');
    Route::post('/projects/{projectId}/review', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/settings', [ClientController::class, 'settings'])->name('settings');
    Route::put('/settings/profile',  [ProfileController::class, 'updateClient'])->name('settings.profile');
    Route::put('/settings/password', [ProfileController::class, 'updateClientPassword'])->name('settings.password');
    Route::post('/settings/photo',   [ProfileController::class, 'uploadClientPhoto'])->name('settings.photo');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'page'])->name('notifications');
    Route::get('/notifications/count', [NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

/*
|--------------------------------------------------------------------------
| Employee Routes
|--------------------------------------------------------------------------
*/

Route::prefix('employee')->name('employee.')->middleware(['role:employee', 'profile.complete', 'no.back'])->group(function () {
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages');
    Route::get('/messages/contacts', [MessageController::class, 'contacts'])->name('messages.contacts');
    Route::get('/messages/thread/{type}/{id}', [MessageController::class, 'thread'])->name('messages.thread');
    Route::post('/messages/send', [MessageController::class, 'send'])->name('messages.send');
    Route::post('/messages/typing', [MessageController::class, 'typing'])->name('messages.typing');
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount'])->name('messages.unread_count');
    Route::get('/projects', [EmployeeController::class, 'projects'])->name('projects');
    Route::get('/project-view/{id}', [ProjectController::class, 'employeeView'])->name('project_view');

    // Submit initial update (tied to a ProgressRequest)
    Route::post('/project-update/{requestId}/submit', [ProjectController::class, 'submitUpdate'])->name('project.submit_update');

    // Submit revision (tied to a project, references parent_update_id)
    Route::post('/project/{id}/submit-revision', [ProjectController::class, 'submitRevision'])->name('project.submit_revision');

    Route::get('/project-materials', [ProjectMaterialController::class, 'employeeIndex'])->name('project_materials');
    Route::get('/project-materials/{projectId}', [ProjectMaterialController::class, 'employeeDetail'])->name('project_materials.detail');
    Route::post('/project-materials/{projectId}/request', [ProjectMaterialController::class, 'requestMaterial'])->name('project_materials.request');

    Route::get('/material-usage/{projectId}', [MaterialUsageController::class, 'employeeDetail'])->name('material_usage.detail');
    Route::post('/material-usage/{projectId}', [MaterialUsageController::class, 'employeeStore'])->name('material_usage.store');
    Route::get('/salary', [EmployeeController::class, 'salary'])->name('salary');
    Route::get('/settings', [EmployeeController::class, 'settings'])->name('settings');
    Route::put('/settings/profile',  [ProfileController::class, 'updateEmployee'])->name('settings.profile');
    Route::put('/settings/password', [ProfileController::class, 'updateEmployeePassword'])->name('settings.password');
    Route::post('/settings/photo',   [ProfileController::class, 'uploadEmployeePhoto'])->name('settings.photo');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'page'])->name('notifications');
    Route::get('/notifications/count', [NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});