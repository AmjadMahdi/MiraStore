<?php

use App\Http\Controllers\StoreProductContactController;
use App\Http\Controllers\StoreProductController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::view('/shein', 'shein.index')->name('shein.index');

Route::get('/store/{vendor:slug}', function (\App\Models\User $vendor) {
    abort_unless($vendor->isVendor(), 404);

    return view('store.show', ['vendor' => $vendor]);
})->name('store.show');

Route::get('/store/{vendor:slug}/{product:slug}', StoreProductController::class)->name('store.product');
Route::get('/store/{vendor:slug}/{product:slug}/contact', StoreProductContactController::class)->name('store.product.contact');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/register', 'auth.register')->name('register');
});

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::view('/dashboard', 'vendor.dashboard')->name('dashboard');

    Route::view('/products', 'vendor.products.index')->name('products.index');
    Route::view('/products/create', 'vendor.products.create')->name('products.create');
    Route::get('/products/{product}/edit', function (\App\Models\Product $product) {
        return view('vendor.products.edit', ['product' => $product]);
    })->name('products.edit');

    Route::view('/analytics', 'vendor.analytics')->name('analytics');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::view('/products', 'admin.products')->name('products.index');
    Route::view('/carts', 'admin.carts')->name('carts.index');
    Route::view('/vendors', 'admin.vendors')->name('vendors.index');
    Route::view('/activity', 'admin.activity')->name('activity.index');
});
