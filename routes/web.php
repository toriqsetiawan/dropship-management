<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataFeedController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShippingUploadController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeAttendanceController;
use App\Http\Controllers\AttendanceController;

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

Route::redirect('/', 'login');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Supplier Routes
    Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier');
    Route::get('/supplier/create', [SupplierController::class, 'create'])->name('supplier.create');
    Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
    Route::get('/supplier/{id}', [SupplierController::class, 'show'])->name('supplier.show');
    Route::get('/supplier/{id}/edit', [SupplierController::class, 'edit'])->name('supplier.edit');
    Route::put('/supplier/{id}', [SupplierController::class, 'update'])->name('supplier.update');
    Route::delete('/supplier/{id}', [SupplierController::class, 'destroy'])->name('supplier.destroy');

    // User Management Routes
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('analytics');
    Route::get('/dashboard/fintech', [DashboardController::class, 'fintech'])->name('fintech');

    // Product Routes
    Route::get('/products', App\Livewire\Product\ProductList::class)->name('products.index');
    Route::get('/products/create', function () {
        return view('pages.product.create');
    })->name('products.create');
    Route::get('/products/{product}/edit', function (App\Models\Product $product) {
        return view('pages.product.edit', compact('product'));
    })->name('products.edit');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');


    Route::fallback(function() {
        return view('pages/utility/404');
    });

    Route::get('language/{locale}', [LanguageController::class, 'switch'])
        ->name('language.switch');

    // Transaction Routes
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{transaction}/download-pdf', [TransactionController::class, 'downloadPdf'])->name('transactions.download_pdf');
    Route::delete('/transactions/bulk-destroy', [TransactionController::class, 'bulkDestroy'])->name('transactions.bulk-destroy');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::post('/transactions/parse-pdf', [TransactionController::class, 'parsePdf'])->name('transactions.parse-pdf');
    Route::get('/transactions/print-pdf/{transaction}', [TransactionController::class, 'printPdfView'])->name('transactions.print-pdf');
    Route::post('/transactions/update-status', [TransactionController::class, 'updateStatus'])->name('transactions.update-status');
    Route::post('/transactions/update-status-by-pdf', [\App\Http\Controllers\TransactionController::class, 'updateStatusByPdf'])->name('transactions.updateStatusByPdf');

    // Employee Routes
    Route::group(['middleware' => ['role:administrator']], function () {
        Route::resource('employees', EmployeeController::class);
        Route::get('/attendance', [EmployeeAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [EmployeeAttendanceController::class, 'store'])->name('attendance.store');
        Route::post('/attendance/generate-month', [EmployeeAttendanceController::class, 'generateMonthAttendance'])->name('attendance.generate-month');
        Route::get('/attendance/manage/{employee}', [EmployeeAttendanceController::class, 'manage'])->name('attendance.manage');
        Route::get('/attendance/payslip/{employee}', [EmployeeAttendanceController::class, 'generatePayslip'])->name('attendance.payslip');
    });
});
