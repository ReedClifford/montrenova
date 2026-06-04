<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\WatchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\PublicWatchController;



Route::get('/', [PublicWatchController::class, 'welcome'])
    ->name('welcome');

Route::get('/watches/{watch}', [PublicWatchController::class, 'show'])
    ->name('public.watches.show');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirect old create route to index and open Add modal
    Route::get('/admin/watches/create', function () {
        return redirect()->route('admin.watches.index', [
            'create' => 1,
        ]);
    })->name('admin.watches.create');

    // Redirect old edit route back to index
    Route::get('/admin/watches/{watch}/edit', function () {
        return redirect()->route('admin.watches.index');
    })->name('admin.watches.edit');

    Route::resource('/admin/watches', WatchController::class)
        ->except(['create', 'edit', 'show'])
        ->names('admin.watches');

    Route::delete('/admin/watch-images/{image}', [WatchController::class, 'deleteImage'])
        ->name('admin.watch-images.destroy');

    Route::patch('/admin/watch-images/{image}/primary', [WatchController::class, 'setPrimaryImage'])
        ->name('admin.watch-images.primary');
    Route::patch('/admin/watches/{watch}/mark-sold', [WatchController::class, 'markSold'])
    ->name('admin.watches.mark-sold');


    Route::patch('/admin/dashboard/starting-cash', [DashboardController::class, 'updateStartingCash'])
        ->name('admin.dashboard.starting-cash.update');

    Route::post('/admin/dashboard/expenses', [DashboardController::class, 'storeExpense'])
        ->name('admin.dashboard.expenses.store');

    Route::delete('/admin/dashboard/expenses/{expense}', [DashboardController::class, 'destroyExpense'])
        ->name('admin.dashboard.expenses.destroy');

    Route::get('/admin/sales', [SalesController::class, 'index'])
        ->name('admin.sales.index');


    Route::patch('/admin/watches/{watch}/reserve', [WatchController::class, 'reserve'])
        ->name('admin.watches.reserve');

    Route::patch('/admin/watches/{watch}/clear-reservation', [WatchController::class, 'clearReservation'])
        ->name('admin.watches.clear-reservation');

        Route::get('/admin/expenses', [ExpenseController::class, 'index'])
    ->name('admin.expenses.index');

Route::post('/admin/expenses', [ExpenseController::class, 'store'])
    ->name('admin.expenses.store');

Route::patch('/admin/expenses/{expense}', [ExpenseController::class, 'update'])
    ->name('admin.expenses.update');

Route::delete('/admin/expenses/{expense}', [ExpenseController::class, 'destroy'])
    ->name('admin.expenses.destroy');



    Route::patch('/admin/watch-images/{image}/primary', [WatchController::class, 'setPrimaryImage'])
        ->name('admin.watch-images.primary');

    Route::patch('/admin/watch-images/{image}/move', [WatchController::class, 'moveImage'])
        ->name('admin.watch-images.move');

    Route::delete('/admin/watch-images/{image}', [WatchController::class, 'deleteImage'])
        ->name('admin.watch-images.destroy');


        
});
require __DIR__.'/auth.php';
