<?php

use App\Http\Controllers\Admin\CartShowController;
use App\Http\Controllers\Admin\ProductEditController as AdminProductEditController;
use App\Http\Controllers\Admin\SheinCartItemsExportController;
use App\Http\Controllers\Admin\StaffEditController;
use App\Http\Controllers\Admin\VendorEditController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\SheinPublicCartController;
use App\Http\Controllers\StoreProductContactController;
use App\Http\Controllers\StoreProductController;
use App\Http\Controllers\StoreShowController;
use App\Http\Controllers\Vendor\ProductEditController as VendorProductEditController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::view('/shein', 'shein.index')->name('shein.index');
Route::view('/cart', 'shein.cart')->name('shein.cart');

Route::get('/shein/shared/{token}', SheinPublicCartController::class)->name('shein.public-cart');

Route::get('/store/{vendor:slug}', StoreShowController::class)->name('store.show');
Route::get('/store/{vendor:slug}/{product:slug}', StoreProductController::class)->name('store.product');
Route::get('/store/{vendor:slug}/{product:slug}/contact', StoreProductContactController::class)->name('store.product.contact');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register')->name('register');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('vendor')->name('vendor.')->group(function () {
    Route::view('/status', 'vendor.application-status')->name('status');
});

Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::view('/dashboard', 'vendor.dashboard')->name('dashboard');

    Route::view('/products', 'vendor.products.index')->name('products.index');
    Route::view('/products/create', 'vendor.products.create')->name('products.create');
    Route::get('/products/{product}/edit', VendorProductEditController::class)->name('products.edit');

    Route::view('/analytics', 'vendor.analytics')->name('analytics');
});

Route::middleware(['auth', 'role:super_admin,supervisor'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::view('/products', 'admin.products')->name('products.index');
    Route::view('/products/order', 'admin.products-order')->name('products.order');
    Route::get('/products/{product}/edit', AdminProductEditController::class)->name('products.edit');
    Route::view('/carts', 'admin.carts')->name('carts.index');
    Route::view('/carts/create', 'admin.carts-create')->name('carts.create');
    Route::get('/carts/{cart}', CartShowController::class)->name('carts.show');
    Route::get('/carts/{cart}/export-items', SheinCartItemsExportController::class)->name('carts.export-items');
    Route::view('/categories', 'admin.categories')->name('categories.index');
    Route::view('/vendors', 'admin.vendors')->name('vendors.index');
    Route::view('/vendors/create', 'admin.vendors-create')->name('vendors.create');
    Route::get('/vendors/{vendor}/edit', VendorEditController::class)->name('vendors.edit');

    Route::view('/activity', 'admin.activity')->name('activity.index');
    Route::view('/settings', 'admin.settings')->name('settings.index');

    // Managing staff (System Administrator / System Supervisor) accounts is
    // restricted to super_admin only — a supervisor cannot create, edit, or
    // delete other staff accounts, including their own.
    Route::middleware('role:super_admin')->group(function () {
        Route::view('/staff', 'admin.staff')->name('staff.index');
        Route::view('/staff/create', 'admin.staff-create')->name('staff.create');
        Route::get('/staff/{staff}/edit', StaffEditController::class)->name('staff.edit');
    });
});
