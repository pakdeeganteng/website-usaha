<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPasswordController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [CustomerAuthController::class, 'createLogin'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('/register', [CustomerAuthController::class, 'createRegistration'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.store');
});

Route::post('/logout', [CustomerAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/menu', [PageController::class, 'menu'])->name('menu.index');
    Route::get('/tentang-kami', [PageController::class, 'about'])->name('about');
    Route::get('/kontak', [PageController::class, 'contact'])->name('contact');
    Route::get('/menu/{product}', [ProductController::class, 'show'])
        ->whereNumber('product')
        ->name('menu.show');
    Route::post('/menu/{product}/order', [CustomerOrderController::class, 'store'])
        ->whereNumber('product')
        ->name('orders.store');
    Route::get('/pesanan', [CustomerOrderController::class, 'index'])->name('orders.index');
});

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('admin.login.store');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(EnsureAdministrator::class)
    ->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
        Route::get('/reports', [FinancialReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/pdf', [FinancialReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('/reports/export/excel', [FinancialReportController::class, 'exportExcel'])->name('reports.export.excel');
        Route::post('/reports/expenses', [FinancialReportController::class, 'storeExpense'])->name('reports.expenses.store');
        Route::post('/reports/refunds', [FinancialReportController::class, 'storeRefund'])->name('reports.refunds.store');
        Route::patch('/reports/orders/{order}/payment', [FinancialReportController::class, 'markPaid'])->name('reports.orders.payment');
        Route::put('/reports/products/{product}/cost', [FinancialReportController::class, 'updateProductCost'])->name('reports.products.cost');
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/password', [AdminPasswordController::class, 'edit'])->name('password.edit');
        Route::put('/password', [AdminPasswordController::class, 'update'])->name('password.update');
    });
