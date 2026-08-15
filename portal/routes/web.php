<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\BundleController;
use App\Http\Controllers\CarrierController;
use App\Http\Controllers\CdrController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DidController;
use App\Http\Controllers\EndpointController;
use App\Http\Controllers\ResellerController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\RatecardController;
use App\Http\Controllers\TariffController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// --- guest ---------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

// --- authenticated -------------------------------------------------------
// Default-deny: everything below requires a session. The web group already
// applies CSRF protection to state-changing verbs (the OV500 portal had CSRF
// disabled globally).
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/live', [DashboardController::class, 'live'])->name('dashboard.live');

    // Self-service password change (reachable even when must_change_password is set)
    Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('password.update');

    // Portal user management — admin only (enforced in the controller too)
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');
    });

    // Carriers — admin-only in slice 1 (role gate) AND policy-checked per action.
    Route::middleware('role:admin')->group(function () {
        Route::get('/carriers', [CarrierController::class, 'index'])->name('carriers.index');
        Route::get('/carriers/create', [CarrierController::class, 'create'])->name('carriers.create');
        Route::post('/carriers', [CarrierController::class, 'store'])->name('carriers.store');
        Route::get('/carriers/{carrier}', [CarrierController::class, 'show'])->name('carriers.show');
        Route::get('/carriers/{carrier}/edit', [CarrierController::class, 'edit'])->name('carriers.edit');
        Route::put('/carriers/{carrier}', [CarrierController::class, 'update'])->name('carriers.update');
    });

    // Balances — admin + reseller (role gate); per-account ownership enforced by
    // AccountPolicy inside each action.
    Route::middleware('role:admin,reseller')->group(function () {
        Route::get('/balances', [BalanceController::class, 'index'])->name('balances.index');
        Route::get('/balances/{account}', [BalanceController::class, 'show'])->name('balances.show');
        Route::post('/balances/{account}/topup', [BalanceController::class, 'topUp'])->name('balances.topup');
    });

    // Tariffs, ratecards, rates — admin-managed pricing (policy-checked per action).
    Route::middleware('role:admin')->group(function () {
        Route::get('/tariffs', [TariffController::class, 'index'])->name('tariffs.index');
        Route::get('/tariffs/create', [TariffController::class, 'create'])->name('tariffs.create');
        Route::post('/tariffs', [TariffController::class, 'store'])->name('tariffs.store');
        Route::get('/tariffs/{tariff}', [TariffController::class, 'show'])->name('tariffs.show');
        Route::get('/tariffs/{tariff}/edit', [TariffController::class, 'edit'])->name('tariffs.edit');
        Route::put('/tariffs/{tariff}', [TariffController::class, 'update'])->name('tariffs.update');
        Route::post('/tariffs/{tariff}/ratecards', [TariffController::class, 'attachRatecard'])->name('tariffs.attach');
        Route::delete('/tariffs/{tariff}/ratecards/{map}', [TariffController::class, 'detachRatecard'])->name('tariffs.detach');

        Route::get('/ratecards', [RatecardController::class, 'index'])->name('ratecards.index');
        Route::get('/ratecards/create', [RatecardController::class, 'create'])->name('ratecards.create');
        Route::post('/ratecards', [RatecardController::class, 'store'])->name('ratecards.store');
        Route::get('/ratecards/{ratecard}', [RatecardController::class, 'show'])->name('ratecards.show');
        Route::get('/ratecards/{ratecard}/edit', [RatecardController::class, 'edit'])->name('ratecards.edit');
        Route::put('/ratecards/{ratecard}', [RatecardController::class, 'update'])->name('ratecards.update');

        // Rates within a ratecard
        Route::post('/ratecards/{ratecard}/rates', [RateController::class, 'store'])->name('rates.store');
        Route::delete('/ratecards/{ratecard}/rates/{prefix}', [RateController::class, 'destroy'])->name('rates.destroy');
        Route::get('/ratecards/{ratecard}/import', [RateController::class, 'bulkForm'])->name('ratecards.bulk');
        Route::post('/ratecards/{ratecard}/import', [RateController::class, 'bulkImport'])->name('rates.bulk.import');

        // Bundles / packages
        Route::get('/bundles', [BundleController::class, 'index'])->name('bundles.index');
        Route::get('/bundles/create', [BundleController::class, 'create'])->name('bundles.create');
        Route::post('/bundles', [BundleController::class, 'store'])->name('bundles.store');
        Route::get('/bundles/{bundle}', [BundleController::class, 'show'])->name('bundles.show');
        Route::get('/bundles/{bundle}/edit', [BundleController::class, 'edit'])->name('bundles.edit');
        Route::put('/bundles/{bundle}', [BundleController::class, 'update'])->name('bundles.update');
        Route::post('/bundles/{bundle}/prefixes', [BundleController::class, 'addPrefix'])->name('bundles.addPrefix');
        Route::delete('/bundles/{bundle}/prefixes/{prefix}', [BundleController::class, 'removePrefix'])->name('bundles.removePrefix');
        Route::post('/bundles/{bundle}/assign', [BundleController::class, 'assign'])->name('bundles.assign');
        Route::delete('/bundles/{bundle}/assign/{assignment}', [BundleController::class, 'unassign'])->name('bundles.unassign');
    });

    // DIDs — admin manages inventory; reseller may view/assign within subtree
    // (create/release are admin-only, enforced by DidPolicy).
    Route::middleware('role:admin,reseller')->group(function () {
        Route::get('/dids', [DidController::class, 'index'])->name('dids.index');
        Route::get('/dids/create', [DidController::class, 'create'])->name('dids.create');
        Route::post('/dids', [DidController::class, 'store'])->name('dids.store');
        Route::get('/dids/{did}', [DidController::class, 'show'])->name('dids.show');
        Route::get('/dids/{did}/edit', [DidController::class, 'edit'])->name('dids.edit');
        Route::put('/dids/{did}', [DidController::class, 'update'])->name('dids.update');
        Route::post('/dids/{did}/release', [DidController::class, 'release'])->name('dids.release');
    });

    // Customers & Resellers — the tenant tree. Reseller sees/creates within its own
    // subtree; ownership enforced by AccountPolicy.
    Route::middleware('role:admin,reseller')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        // delete/archive is admin-only (enforced by AccountPolicy::delete)
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::get('/resellers', [ResellerController::class, 'index'])->name('resellers.index');
        Route::get('/resellers/create', [ResellerController::class, 'create'])->name('resellers.create');
        Route::post('/resellers', [ResellerController::class, 'store'])->name('resellers.store');
        Route::get('/resellers/{reseller}', [ResellerController::class, 'show'])->name('resellers.show');
        Route::get('/resellers/{reseller}/edit', [ResellerController::class, 'edit'])->name('resellers.edit');
        Route::put('/resellers/{reseller}', [ResellerController::class, 'update'])->name('resellers.update');
    });

    // Endpoints (SIP accounts) — customer may view their own; create/edit admin+reseller
    // (enforced by SipAccountPolicy).
    Route::middleware('role:admin,reseller,customer')->group(function () {
        Route::get('/endpoints', [EndpointController::class, 'index'])->name('endpoints.index');
        Route::get('/endpoints/create', [EndpointController::class, 'create'])->name('endpoints.create');
        Route::post('/endpoints', [EndpointController::class, 'store'])->name('endpoints.store');
        Route::get('/endpoints/{endpoint}', [EndpointController::class, 'show'])->name('endpoints.show');
        Route::get('/endpoints/{endpoint}/secret', [EndpointController::class, 'secret'])->name('endpoints.secret');
        Route::get('/endpoints/{endpoint}/edit', [EndpointController::class, 'edit'])->name('endpoints.edit');
        Route::put('/endpoints/{endpoint}', [EndpointController::class, 'update'])->name('endpoints.update');

        // CDRs / reports — tenancy-scoped inside the controller
        Route::get('/cdrs', [CdrController::class, 'index'])->name('cdrs.index');
    });
});
