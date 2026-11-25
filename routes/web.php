<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminBannerController;
use App\Http\Controllers\AdminBuyerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminVoucherController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\JokiOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('catalog');
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.all');
Route::get('/kategori/{category:slug}', [CatalogController::class, 'index'])->name('categories.show');
Route::get('/akun/{account:slug}', [CatalogController::class, 'show'])->name('accounts.show');
Route::post('/suka/{account}', [CatalogController::class, 'like'])->middleware('throttle:60,1')->name('accounts.like');
Route::view('/cara-transaksi', 'catalog.how')->name('how');
Route::view('/joki-ml', 'catalog.joki')->name('joki');
Route::get('/flash-sale', [CatalogController::class, 'flashSale'])->name('flash-sale');
Route::get('/rental', [CatalogController::class, 'rental'])->name('rental');
Route::view('/tentang', 'catalog.about')->name('about');
Route::view('/profil', 'catalog.profile')->name('profile');
Route::get('/laporan', [ReportController::class, 'index'])->name('reports');
Route::post('/laporan', [ReportController::class, 'store'])->middleware('throttle:6,10')->name('reports.store');
Route::get('/laporan/{report}', [ReportController::class, 'show'])->whereNumber('report')->name('reports.show');
Route::get('/kritik-saran', [FeedbackController::class, 'create'])->name('feedback');
Route::post('/kritik-saran', [FeedbackController::class, 'store'])->middleware('throttle:5,1')->name('feedback.store');
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
Route::get('/wishlist/accounts', [WishlistController::class, 'accounts'])->middleware('throttle:60,1')->name('wishlist.accounts');
// Instalment sales were discontinued (2026-11-23): old /cicilan links go to the catalog.
Route::permanentRedirect('/cicilan', '/katalog');
Route::get('/keranjang', [CartController::class, 'index'])->name('cart');
Route::get('/keranjang/accounts', [CartController::class, 'accounts'])->middleware('throttle:60,1')->name('cart.accounts');
Route::view('/kebijakan-privasi', 'catalog.privacy')->name('privacy');
Route::view('/ketentuan-layanan', 'catalog.terms')->name('terms');
Route::view('/kebijakan-refund', 'catalog.refund')->name('refund');
// Tripay posts payment results here (signed with the private key; CSRF is skipped in bootstrap/app.php).
Route::post('/payment/tripay/callback', [OrderController::class, 'callback'])->middleware('throttle:120,1')->name('payment.tripay.callback');
Route::middleware('guest')->group(function () {
    Route::view('/'.config('app.admin_path').'/login', 'auth.login')->name('login');
    Route::post('/'.config('app.admin_path').'/login', [AuthController::class, 'login'])->middleware('throttle:admin-login');
    Route::get('/masuk', [AccountController::class, 'showLogin'])->name('account.login');
    Route::post('/masuk', [AccountController::class, 'login'])->middleware('throttle:account-auth');
    Route::get('/daftar', [AccountController::class, 'showRegister'])->name('account.register');
    Route::post('/daftar', [AccountController::class, 'register'])->middleware('throttle:account-auth');
    Route::get('/lupa-password', [AccountController::class, 'showForgot'])->name('password.request');
    Route::post('/lupa-password', [AccountController::class, 'sendResetLink'])->middleware('throttle:account-auth')->name('password.email');
    Route::post('/lupa-password/whatsapp', [AccountController::class, 'requestResetViaWhatsapp'])->middleware('throttle:account-auth')->name('password.whatsapp');
    Route::get('/reset-password/{token}', [AccountController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AccountController::class, 'reset'])->middleware('throttle:account-auth')->name('password.update');
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('throttle:account-auth')->name('google.callback');
});
Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AccountController::class, 'logout'])->name('account.logout');
    Route::put('/akun/profil', [AccountController::class, 'updateProfile'])->name('account.profile');
    Route::get('/profil/pengaturan', [AccountController::class, 'settings'])->name('account.settings');
    Route::get('/profil/password', [AccountController::class, 'showPassword'])->name('account.password');
    Route::put('/profil/password', [AccountController::class, 'updatePassword'])->middleware('throttle:account-auth');
    Route::post('/akun/wishlist', [AccountController::class, 'syncWishlist'])->middleware('throttle:60,1')->name('account.wishlist');
    // Website checkout through Tripay (the super admin's accounts) and the buyer's orders.
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    // Joki paid on the website: the joki form posts its brief here, then continues to /checkout?joki=1.
    Route::post('/joki/bayar', [JokiOrderController::class, 'prepare'])->middleware('throttle:20,1')->name('joki.prepare');
    Route::get('/joki/masuk', [JokiOrderController::class, 'signIn'])->name('joki.sign-in');
    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/pesanan/{order}/status', [OrderController::class, 'status'])->middleware('throttle:30,1')->name('orders.status');
    // Live chat with the store (signed-in buyers; ChatController refuses admins).
    Route::get('/chat/pesan', [ChatController::class, 'messages'])->middleware('throttle:120,1')->name('chat.messages');
    Route::post('/chat/pesan', [ChatController::class, 'send'])->middleware('throttle:20,1')->name('chat.send');
});
// The admin area lives under a configurable, hard-to-guess prefix (ADMIN_PATH, see config/app.php).
Route::middleware(['auth', 'admin'])->prefix(config('app.admin_path'))->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/password', [AdminUserController::class, 'showOwnPassword'])->name('password');
    Route::put('/password', [AdminUserController::class, 'updateOwnPassword'])->middleware('throttle:admin-accounts')->name('password.update');
    Route::post('/profil/foto', [AdminUserController::class, 'updateAvatar'])->middleware('throttle:20,1')->name('avatar.update');
    Route::delete('/profil/foto', [AdminUserController::class, 'destroyAvatar'])->name('avatar.destroy');

    // Every admin: their own game & rental accounts (the controller checks ownership) and the inbox.
    Route::get('/accounts', [AdminController::class, 'accounts'])->name('accounts');
    Route::get('/rentals', [AdminController::class, 'rentals'])->name('rentals');
    Route::patch('/rentals/{account}/status', [AdminController::class, 'updateRentalStatus'])->name('rentals.status');
    Route::patch('/accounts/{account}/payment', [AdminController::class, 'updatePayment'])->name('accounts.payment');
    Route::get('/accounts/create', [AdminController::class, 'form'])->name('accounts.create');
    Route::post('/accounts', [AdminController::class, 'store'])->name('accounts.store');
    Route::get('/accounts/{account}/edit', [AdminController::class, 'form'])->name('accounts.edit');
    Route::put('/accounts/{account}', [AdminController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts', [AdminController::class, 'destroyMany'])->name('accounts.destroy-many');
    Route::delete('/accounts/{account}', [AdminController::class, 'destroy'])->name('accounts.destroy');
    Route::get('/messages', [FeedbackController::class, 'index'])->name('messages');
    Route::post('/messages/read-all', [FeedbackController::class, 'markAllRead'])->name('messages.read-all');
    Route::patch('/messages/{message}/read', [FeedbackController::class, 'markRead'])->name('messages.read');
    Route::delete('/messages/{message}', [FeedbackController::class, 'destroy'])->name('messages.destroy');

    // Super admin only: store-wide settings, sales, buyers and the admin team.
    Route::middleware('super-admin')->group(function () {
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
        Route::patch('/orders/{order}/delivered', [AdminOrderController::class, 'deliver'])->name('orders.deliver');
        Route::get('/orders/{order}/chat', [AdminOrderController::class, 'chat'])->name('orders.chat');
        Route::patch('/orders-gateway', [AdminOrderController::class, 'toggleGateway'])->name('orders.gateway');
        Route::patch('/accounts/{account}/gateway', [AdminController::class, 'toggleAccountGateway'])->name('accounts.gateway');
        Route::get('/vouchers', [AdminVoucherController::class, 'index'])->name('vouchers');
        Route::post('/vouchers', [AdminVoucherController::class, 'store'])->name('vouchers.store');
        Route::patch('/vouchers/{voucher}', [AdminVoucherController::class, 'toggle'])->name('vouchers.toggle');
        Route::delete('/vouchers/{voucher}', [AdminVoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::get('/sales', [AdminController::class, 'sales'])->name('sales');
        Route::post('/sales', [AdminController::class, 'storeSale'])->name('sales.store');
        Route::delete('/sales/{manualSale}', [AdminController::class, 'destroySale'])->name('sales.destroy');
        Route::get('/categories', [AdminController::class, 'categories'])->name('categories');
        Route::post('/categories', [AdminController::class, 'saveCategory'])->name('categories.store');
        Route::put('/categories/{category}', [AdminController::class, 'saveCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminController::class, 'deleteCategory'])->name('categories.destroy');
        Route::get('/admins', [AdminUserController::class, 'index'])->name('admins');
        Route::post('/admins', [AdminUserController::class, 'store'])->middleware('throttle:admin-accounts')->name('admins.store');
        Route::patch('/admins/{user}/role', [AdminUserController::class, 'updateRole'])->middleware('throttle:admin-accounts')->name('admins.role');
        Route::put('/admins/{user}', [AdminUserController::class, 'update'])->middleware('throttle:admin-accounts')->name('admins.update');
        Route::put('/admins/{user}/password', [AdminUserController::class, 'resetPassword'])->middleware('throttle:admin-accounts')->name('admins.password');
        Route::get('/chat', [ChatController::class, 'adminIndex'])->name('chat');
        Route::get('/chat/{conversation}', [ChatController::class, 'adminShow'])->name('chat.show');
        Route::get('/chat/{conversation}/pesan', [ChatController::class, 'adminMessages'])->middleware('throttle:120,1')->name('chat.messages');
        Route::post('/chat/{conversation}/pesan', [ChatController::class, 'adminReply'])->middleware('throttle:60,1')->name('chat.reply');
        Route::delete('/chat/{conversation}', [ChatController::class, 'adminDestroy'])->name('chat.destroy');
        Route::get('/chat-unread', [ChatController::class, 'adminUnread'])->middleware('throttle:120,1')->name('chat.unread');
        Route::delete('/admins/{user}', [AdminUserController::class, 'destroy'])->middleware('throttle:admin-accounts')->name('admins.destroy');
        Route::get('/buyers', [AdminBuyerController::class, 'index'])->name('buyers');
        Route::post('/buyers/{user}/password', [AdminBuyerController::class, 'resetPassword'])->name('buyers.password');
        Route::get('/banner', [AdminBannerController::class, 'index'])->name('banner');
        Route::post('/banner', [AdminBannerController::class, 'store'])->name('banner.store');
        Route::put('/banner/{banner}', [AdminBannerController::class, 'update'])->name('banner.update');
        Route::delete('/banner/{banner}', [AdminBannerController::class, 'destroy'])->name('banner.destroy');
        Route::get('/infos', [AdminController::class, 'infos'])->name('infos');
        Route::post('/infos', [AdminController::class, 'saveInfo'])->name('infos.store');
        Route::put('/infos/{info}', [AdminController::class, 'saveInfo'])->name('infos.update');
        Route::delete('/infos/{info}', [AdminController::class, 'deleteInfo'])->name('infos.destroy');
        Route::get('/contacts', [AdminController::class, 'contacts'])->name('contacts');
        Route::post('/contacts', [AdminController::class, 'saveContact'])->name('contacts.store');
        Route::put('/contacts/{contact}', [AdminController::class, 'saveContact'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [AdminController::class, 'deleteContact'])->name('contacts.destroy');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports');
        Route::patch('/reports/{report}', [AdminReportController::class, 'update'])->name('reports.update');
        Route::delete('/reports/{report}', [AdminReportController::class, 'destroy'])->name('reports.destroy');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::put('/settings', [AdminController::class, 'saveSettings'])->name('settings.update');
    });
});
