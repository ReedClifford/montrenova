<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\WatchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicWarrantyController;
use App\Http\Controllers\PublicWatchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicWatchController::class, 'welcome'])
    ->name('welcome');

Route::get('/sold-watches', [PublicWatchController::class, 'soldGallery'])
    ->name('public.sold-watches.index');

Route::get('/warranty-check', [PublicWarrantyController::class, 'index'])
    ->name('public.warranty-check.index');

Route::post('/warranty-check', [PublicWarrantyController::class, 'check'])
    ->name('public.warranty-check.check');

Route::get('/watches/{watch}', [PublicWatchController::class, 'show'])
    ->name('public.watches.show');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Watch Redirect Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/watches/create', function () {
        return redirect()->route('admin.watches.index', [
            'create' => 1,
        ]);
    })->name('admin.watches.create');

    Route::get('/admin/watches/{watch}/edit', function () {
        return redirect()->route('admin.watches.index');
    })->name('admin.watches.edit');

    /*
    |--------------------------------------------------------------------------
    | Watch Custom Routes
    |--------------------------------------------------------------------------
    |
    | Important:
    | These routes must stay BEFORE Route::resource('/admin/watches')
    | so Laravel does not treat "reorder" as a {watch} parameter.
    |
    */

    Route::patch('/admin/watches/reorder', [WatchController::class, 'reorder'])
        ->name('admin.watches.reorder');

    Route::patch('/admin/watches/{watch}/mark-sold', [WatchController::class, 'markSold'])
        ->name('admin.watches.mark-sold');

    Route::patch('/admin/watches/{watch}/reserve', [WatchController::class, 'reserve'])
        ->name('admin.watches.reserve');

    Route::patch('/admin/watches/{watch}/clear-reservation', [WatchController::class, 'clearReservation'])
        ->name('admin.watches.clear-reservation');

    /*
    |--------------------------------------------------------------------------
    | Watch Image Routes
    |--------------------------------------------------------------------------
    */

    Route::delete('/admin/watch-images/{image}', [WatchController::class, 'deleteImage'])
        ->name('admin.watch-images.destroy');

    Route::patch('/admin/watch-images/{image}/primary', [WatchController::class, 'setPrimaryImage'])
        ->name('admin.watch-images.primary');

    Route::patch('/admin/watch-images/{image}/move', [WatchController::class, 'moveImage'])
        ->name('admin.watch-images.move');

    /*
    |--------------------------------------------------------------------------
    | Watch Resource Routes
    |--------------------------------------------------------------------------
    */

    Route::resource('/admin/watches', WatchController::class)
        ->except(['create', 'edit', 'show'])
        ->names('admin.watches');

    /*
    |--------------------------------------------------------------------------
    | Dashboard Money Routes
    |--------------------------------------------------------------------------
    */

    Route::patch('/admin/dashboard/starting-cash', [DashboardController::class, 'updateStartingCash'])
        ->name('admin.dashboard.starting-cash.update');

    Route::post('/admin/dashboard/expenses', [DashboardController::class, 'storeExpense'])
        ->name('admin.dashboard.expenses.store');

    Route::delete('/admin/dashboard/expenses/{expense}', [DashboardController::class, 'destroyExpense'])
        ->name('admin.dashboard.expenses.destroy');

    /*
    |--------------------------------------------------------------------------
    | Sales Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/sales', [SalesController::class, 'index'])
        ->name('admin.sales.index');

    /*
    |--------------------------------------------------------------------------
    | Expense Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/expenses', [ExpenseController::class, 'index'])
        ->name('admin.expenses.index');

    Route::post('/admin/expenses', [ExpenseController::class, 'store'])
        ->name('admin.expenses.store');

    Route::patch('/admin/expenses/{expense}', [ExpenseController::class, 'update'])
        ->name('admin.expenses.update');

    Route::delete('/admin/expenses/{expense}', [ExpenseController::class, 'destroy'])
        ->name('admin.expenses.destroy');
});

require __DIR__ . '/auth.php';