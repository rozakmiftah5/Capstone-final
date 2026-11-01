<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterMaterialController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialReceiptController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\PoHistoryController;
use App\Http\Controllers\PriceCheckController;
use App\Http\Controllers\StockOsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect halaman utama ke login
Route::get('/', function (Request $request) {
    if (Auth::check()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    return redirect()->route('login');
});

// Route Auth
Route::get('/login', [AuthController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Route yang dilindungi middleware (Harus Login)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('warehouses', WarehouseController::class);
    Route::resource('users', UserController::class)->middleware('can:admin');
    Route::resource('materials', MaterialController::class);
    Route::middleware('can:material-receipts')->group(function (): void {
        Route::get('/material-receipts', [MaterialReceiptController::class, 'index'])->name('material-receipts.index');
        Route::post('/material-receipts', [MaterialReceiptController::class, 'store'])->name('material-receipts.store');
    });

    Route::get('/master-materials', [MasterMaterialController::class, 'index'])->name('master-materials.index');
    Route::post('/master-materials/import', [MasterMaterialController::class, 'import'])
        ->middleware('can:admin')->name('master-materials.import');
    Route::get('/po-history', [PoHistoryController::class, 'index'])->name('po-history.index');
    Route::post('/po-history/import', [PoHistoryController::class, 'import'])
        ->middleware('can:admin')->name('po-history.import');
    Route::get('/price-check', PriceCheckController::class)->name('price-check');

    Route::get('/stock-os', [StockOsController::class, 'index'])->name('stock-os.index');
    Route::get('/stock-os/export', [StockOsController::class, 'export'])->name('stock-os.export');
    Route::get('/stock-os/{stockOsItem}/history', [StockOsController::class, 'history'])->name('stock-os.history');
    Route::middleware('can:admin')->group(function (): void {
        Route::post('/stock-os', [StockOsController::class, 'store'])->name('stock-os.store');
        Route::get('/stock-os/{stockOsItem}/edit', [StockOsController::class, 'edit'])->name('stock-os.edit');
        Route::put('/stock-os/{stockOsItem}', [StockOsController::class, 'update'])->name('stock-os.update');
        Route::delete('/stock-os/{stockOsItem}', [StockOsController::class, 'destroy'])->name('stock-os.destroy');
    });
    Route::post('/stock-os/{stockOsItem}/use', [StockOsController::class, 'useStock'])
        ->middleware('can:stock-operations')->name('stock-os.use');
    Route::post('/stock-os/{stockOsItem}/receive', [StockOsController::class, 'receiveStock'])
        ->middleware('can:stock-receipts')->name('stock-os.receive');

    Route::get('/material-requests/create', [MaterialRequestController::class, 'create'])->name('material-requests.create');
    Route::post('/material-requests', [MaterialRequestController::class, 'store'])->name('material-requests.store');
    Route::get('/material-requests/{materialRequest}/download', [MaterialRequestController::class, 'download'])->name('material-requests.download');
    Route::get('/material-requests/{materialRequest}', [MaterialRequestController::class, 'show'])->name('material-requests.show');
    Route::middleware('can:admin')->group(function (): void {
        Route::get('/material-requests', [MaterialRequestController::class, 'index'])->name('material-requests.index');
        Route::post('/material-requests/{materialRequest}/complete', [MaterialRequestController::class, 'complete'])->name('material-requests.complete');
        Route::post('/material-requests/{materialRequest}/read', [MaterialRequestController::class, 'markRead'])->name('material-requests.read');
    });

});
