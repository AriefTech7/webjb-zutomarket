<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'index'])->name('catalog');
Route::get('/kategori/{category:slug}', [CatalogController::class, 'index'])->name('categories.show');
Route::get('/akun/{account:slug}', [CatalogController::class, 'show'])->name('accounts.show');
Route::view('/cara-transaksi', 'catalog.how')->name('how');
Route::middleware('guest')->group(function () {
    Route::view('/admin/login', 'auth.login')->name('login');
    Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/accounts', [AdminController::class, 'accounts'])->name('accounts');
    Route::get('/accounts/create', [AdminController::class, 'form'])->name('accounts.create');
    Route::post('/accounts', [AdminController::class, 'store'])->name('accounts.store');
    Route::get('/accounts/{account}/edit', [AdminController::class, 'form'])->name('accounts.edit');
    Route::put('/accounts/{account}', [AdminController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{account}', [AdminController::class, 'destroy'])->name('accounts.destroy');
    Route::get('/categories', [AdminController::class, 'categories'])->name('categories');
    Route::post('/categories', [AdminController::class, 'saveCategory'])->name('categories.store');
    Route::put('/categories/{category}', [AdminController::class, 'saveCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminController::class, 'deleteCategory'])->name('categories.destroy');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::put('/settings', [AdminController::class, 'saveSettings'])->name('settings.update');
});
