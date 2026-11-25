<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\DashboardSettingsController;
use App\Http\Controllers\HelpdeskApiKeyController;
use App\Http\Controllers\HelpdeskConfigurationController;
use App\Http\Controllers\HelpdeskController;
use App\Http\Controllers\HelpdeskEmailController;
use App\Http\Controllers\HelpdeskTaskController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserManagementController;
use App\Http\Middleware\HelpdeskApiAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect(AuthController::workspaceUrl(auth()->user()));
    }

    return app(HelpdeskController::class)->create(request());
});
Route::get('/login', function () {
    return auth()->check() ? redirect(AuthController::workspaceUrl(auth()->user())) : view('login');
})->name('login');
Route::get('/register', [CustomerAccountController::class, 'registerForm'])->middleware('guest')->name('register');
Route::post('/register', [CustomerAccountController::class, 'register'])->middleware(['guest', 'throttle:6,1'])->name('register.store');
Route::get('/forgot-password', [CustomerAccountController::class, 'forgotForm'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [CustomerAccountController::class, 'forgot'])->middleware(['guest', 'throttle:6,1'])->name('password.email');
Route::get('/reset-password/{token}', [CustomerAccountController::class, 'resetForm'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [CustomerAccountController::class, 'reset'])->middleware(['guest', 'throttle:6,1'])->name('password.update');

Route::get('/knowledge', [KnowledgeBaseController::class, 'index'])->name('knowledge.index');
Route::get('/knowledge/{article}', [KnowledgeBaseController::class, 'show'])->name('knowledge.show');
Route::get('/support/new', [HelpdeskController::class, 'create'])->name('support.create');
Route::post('/support/new', [HelpdeskController::class, 'store'])->middleware('throttle:20,1')->name('support.store');
Route::post('/api/mail/inbound', [HelpdeskEmailController::class, 'inbound'])->middleware('throttle:60,1');
Route::get('/integration/v1/tickets', [TicketController::class, 'index'])->middleware([HelpdeskApiAccess::class.':read', 'module:tickets.view', 'throttle:60,1']);
Route::post('/integration/v1/tickets', [TicketController::class, 'store'])->middleware([HelpdeskApiAccess::class.':create', 'module:tickets.create', 'throttle:20,1']);

Route::middleware('auth')->prefix('helpdesk')->name('helpdesk.')->group(function () {
    Route::get('/', [HelpdeskController::class, 'index'])->middleware('module:tickets.view')->name('index');
    Route::get('/new', [HelpdeskController::class, 'create'])->middleware('module:tickets.create')->name('create');
    Route::post('/tickets', [HelpdeskController::class, 'store'])->middleware(['module:tickets.create', 'throttle:20,1'])->name('store');
    Route::get('/tickets/export', [HelpdeskController::class, 'export'])->middleware(['module:tickets.view', 'module:tickets.export'])->name('export');
    Route::post('/tickets/bulk', [HelpdeskController::class, 'bulk'])->middleware(['module:tickets.view', 'module:tickets.status'])->name('bulk');
    Route::get('/tickets/{ticket}', [HelpdeskController::class, 'show'])->middleware('module:tickets.view')->name('ticket');
    Route::patch('/tickets/{ticket}', [HelpdeskController::class, 'update'])->middleware('module:tickets.view')->name('update');
    Route::post('/tickets/{ticket}/reply', [HelpdeskController::class, 'reply'])->middleware('module:tickets.view')->name('reply');
    Route::post('/tickets/{ticket}/attachment', [HelpdeskController::class, 'attachment'])->middleware('module:tickets.view')->name('attachment');
    Route::get('/attachments/{attachment}', [HelpdeskController::class, 'download'])->middleware('module:tickets.view')->name('download');
    Route::get('/tasks', [HelpdeskTaskController::class, 'index'])->middleware('module:tasks.manage')->name('tasks');
    Route::post('/tasks', [HelpdeskTaskController::class, 'store'])->middleware('module:tasks.manage')->name('tasks.store');
    Route::patch('/tasks/{task}', [HelpdeskTaskController::class, 'update'])->middleware('module:tasks.manage')->name('tasks.update');
    Route::get('/directory', [HelpdeskController::class, 'directory'])->middleware('module:directory.view')->name('directory');
    Route::get('/reports', [HelpdeskController::class, 'reports'])->middleware('module:analytics.view')->name('reports');
    Route::get('/settings', [DashboardSettingsController::class, 'index'])->middleware('module:users.manage')->name('settings');
    Route::post('/settings', [DashboardSettingsController::class, 'save'])->middleware('module:users.manage')->name('settings.save');
    Route::get('/audit', [HelpdeskController::class, 'audit'])->middleware('module:users.manage')->name('audit');
    Route::get('/api-keys', [HelpdeskApiKeyController::class, 'index'])->middleware('module:users.manage')->name('api-keys');
    Route::post('/api-keys', [HelpdeskApiKeyController::class, 'store'])->middleware('module:users.manage')->name('api-keys.store');
    Route::patch('/api-keys/{key}', [HelpdeskApiKeyController::class, 'revoke'])->middleware('module:users.manage')->name('api-keys.revoke');
    Route::get('/email', [HelpdeskEmailController::class, 'index'])->middleware('module:users.manage')->name('email');
    Route::post('/email', [HelpdeskEmailController::class, 'save'])->middleware('module:users.manage')->name('email.save');
    Route::post('/email/test', [HelpdeskEmailController::class, 'test'])->middleware('module:users.manage')->name('email.test');
    Route::get('/manage/{kind}', [HelpdeskConfigurationController::class, 'index'])->name('manage');
    Route::post('/manage/{kind}', [HelpdeskConfigurationController::class, 'save'])->name('manage.store');
    Route::put('/manage/{kind}/{record}', [HelpdeskConfigurationController::class, 'save'])->name('manage.update');
    Route::post('/logout', function (Request $request) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

Route::middleware(['auth', 'module:users.manage'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
});

Route::prefix('api')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth');
    Route::get('/categories', [TicketController::class, 'getCategories']);
    Route::get('/agents', [TicketController::class, 'getAgents'])->middleware('auth');
    Route::get('/tickets', [TicketController::class, 'index'])->middleware(['auth', 'module:tickets.view']);
    Route::get('/analytics', [TicketController::class, 'index'])->middleware(['auth', 'module:analytics.view', 'module:tickets.view']);
    Route::post('/tickets', [TicketController::class, 'store'])->middleware('throttle:20,1');
    Route::post('/tickets/{ticket}/comments', [TicketController::class, 'storeComment'])->middleware(['auth', 'module:tickets.comment']);
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->middleware(['auth', 'module:tickets.status']);
    Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assignAgent'])->middleware(['auth', 'module:tickets.assign']);
});
