<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('catalog');
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.all');
Route::get('/kategori/{category:slug}', [CatalogController::class, 'index'])->name('categories.show');
Route::get('/akun/{account:slug}', [CatalogController::class, 'show'])->name('accounts.show');
Route::view('/cara-transaksi', 'catalog.how')->name('how');
Route::view('/joki-ml', 'catalog.joki')->name('joki');
Route::get('/flash-sale', [CatalogController::class, 'flashSale'])->name('flash-sale');
Route::get('/rental', [CatalogController::class, 'rental'])->name('rental');
Route::view('/tentang', 'catalog.about')->name('about');
Route::view('/profil', 'catalog.profile')->name('profile');
Route::view('/kritik-saran', 'catalog.feedback')->name('feedback');
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
Route::get('/wishlist/accounts', [WishlistController::class, 'accounts'])->middleware('throttle:60,1')->name('wishlist.accounts');
Route::middleware('guest')->group(function () {
    Route::view('/admin/login', 'auth.login')->name('login');
    Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:admin-login');
});
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/accounts', [AdminController::class, 'accounts'])->name('accounts');
    Route::get('/rentals', [AdminController::class, 'rentals'])->name('rentals');
    Route::patch('/rentals/{account}/status', [AdminController::class, 'updateRentalStatus'])->name('rentals.status');
    Route::get('/sales', [AdminController::class, 'sales'])->name('sales');
    Route::post('/sales', [AdminController::class, 'storeSale'])->name('sales.store');
    Route::delete('/sales/{manualSale}', [AdminController::class, 'destroySale'])->name('sales.destroy');
    Route::get('/accounts/create', [AdminController::class, 'form'])->name('accounts.create');
    Route::post('/accounts', [AdminController::class, 'store'])->name('accounts.store');
    Route::get('/accounts/{account}/edit', [AdminController::class, 'form'])->name('accounts.edit');
    Route::put('/accounts/{account}', [AdminController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts', [AdminController::class, 'destroyMany'])->name('accounts.destroy-many');
    Route::delete('/accounts/{account}', [AdminController::class, 'destroy'])->name('accounts.destroy');
    Route::get('/categories', [AdminController::class, 'categories'])->name('categories');
    Route::post('/categories', [AdminController::class, 'saveCategory'])->name('categories.store');
    Route::put('/categories/{category}', [AdminController::class, 'saveCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminController::class, 'deleteCategory'])->name('categories.destroy');
    Route::get('/infos', [AdminController::class, 'infos'])->name('infos');
    Route::post('/infos', [AdminController::class, 'saveInfo'])->name('infos.store');
    Route::put('/infos/{info}', [AdminController::class, 'saveInfo'])->name('infos.update');
    Route::delete('/infos/{info}', [AdminController::class, 'deleteInfo'])->name('infos.destroy');
    Route::get('/contacts', [AdminController::class, 'contacts'])->name('contacts');
    Route::post('/contacts', [AdminController::class, 'saveContact'])->name('contacts.store');
    Route::put('/contacts/{contact}', [AdminController::class, 'saveContact'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [AdminController::class, 'deleteContact'])->name('contacts.destroy');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::put('/settings', [AdminController::class, 'saveSettings'])->name('settings.update');
});
