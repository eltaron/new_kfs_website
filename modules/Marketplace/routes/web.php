<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\CartController;
use Modules\Marketplace\Http\Controllers\CheckoutController;
use Modules\Marketplace\Http\Controllers\OrderController;
use Modules\Marketplace\Http\Controllers\ReviewController;
use Modules\Marketplace\Http\Controllers\StoreController;
use Modules\Marketplace\Http\Controllers\VendorApplicationController;
use Modules\Marketplace\Http\Controllers\VendorDocumentController;
use Modules\Marketplace\Http\Controllers\WishlistController;

/*
|--------------------------------------------------------------------------
| متجر ذوي الهمم — public storefront (/store)
|--------------------------------------------------------------------------
*/

Route::prefix('store')->name('market.')->group(function () {
    // Browsing
    Route::get('/', [StoreController::class, 'home'])->name('home');
    Route::get('/products', [StoreController::class, 'index'])->name('products.index');
    Route::get('/category/{slug}', [StoreController::class, 'category'])->name('categories.show');
    Route::get('/product/{slug}', [StoreController::class, 'show'])->name('products.show');
    Route::get('/seller/{slug}', [StoreController::class, 'vendor'])->name('vendors.show');

    // Cart (guest or signed-in)
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart', [CartController::class, 'store'])->middleware('throttle:60,1')->name('cart.store');
    Route::patch('/cart/{item}', [CartController::class, 'update'])->whereNumber('item')->middleware('throttle:60,1')->name('cart.update');
    Route::delete('/cart/{item}', [CartController::class, 'destroy'])->whereNumber('item')->name('cart.destroy');

    // Become a seller — landing is public
    Route::get('/sell', [VendorApplicationController::class, 'landing'])->name('sell');

    Route::middleware('auth')->group(function () {
        Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout');
        Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/pay', [OrderController::class, 'pay'])->middleware('throttle:10,1')->name('orders.pay');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        Route::post('/product/{slug}/reviews', [ReviewController::class, 'store'])->middleware('throttle:10,1')->name('reviews.store');

        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->whereNumber('product')->name('wishlist.toggle');

        Route::get('/sell/apply', [VendorApplicationController::class, 'create'])->name('sell.apply');
        Route::post('/sell/apply', [VendorApplicationController::class, 'store'])->middleware('throttle:5,10')->name('sell.store');
        Route::get('/sell/status', [VendorApplicationController::class, 'status'])->name('sell.status');

        // Private disability-card scan (vendor owner or marketplace admins)
        Route::get('/vendors/{vendor}/document', VendorDocumentController::class)->whereNumber('vendor')->name('vendors.document');
    });
});
