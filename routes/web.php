<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Main\ManageProductsController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\MaterialCategoryController;
use App\Http\Controllers\MaterialTextureController;
use App\Http\Controllers\MaterialSleeveController;
use App\Http\Controllers\MaterialSizeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShippingController;

use App\Http\Controllers\Main\ManageUsersSalesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ManageTaskController;

/* ================= DEFAULT INDEX / LOGIN ================= */

Route::get('/', fn() => redirect('/login'));
Route::get('/login', function () {
    if (Auth::check()) {
        $user = Auth::user();
        switch ($user->role) {
            case 'owner':
                return redirect()->route('owner.dashboard');
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'pm':
                return redirect()->route('pm.dashboard');
            case 'karyawan':
                return redirect()->route('karyawan.dashboard');
        }
    }
    return app(LoginController::class)->showLoginForm();
})->name('login');

Route::post('/', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/* ================= ROLE-BASED DASHBOARD ================= */
Route::middleware(['auth'])->group(function () {

    /* ---------- OWNER ---------- */
    Route::prefix('owner')->name('owner.')->middleware('role:owner')->group(function () {
        // Dashboard
        Route::get('dashboard', fn() => view('pages.owner.dashboard'))->name('dashboard');

        // Revenue
        Route::get('revenue', fn() => view('pages.owner.revenue'))->name('revenue');

        // Payment History - Owner can see and approve/reject
        Route::get('payment-history', [\App\Http\Controllers\PaymentHistoryController::class, 'index'])->name('payment-history');
        Route::patch('payments/{payment}/approve', [\App\Http\Controllers\PaymentController::class, 'approve'])->name('payments.approve');
        Route::patch('payments/{payment}/reject', [\App\Http\Controllers\PaymentController::class, 'reject'])->name('payments.reject');

        // Manage Data
        Route::prefix('manage-data')->name('manage-data.')->group(function () {
            // Products
            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', [ManageProductsController::class, 'index'])->name('index');
                Route::resource('product-categories', ProductCategoryController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('material-categories', MaterialCategoryController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('material-textures', MaterialTextureController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('material-sleeves', MaterialSleeveController::class)
                    ->parameters([
                        'material-sleeves' => 'materialSleeve'
                    ]);
                Route::resource('material-sizes', MaterialSizeController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('services', ServiceController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('shippings', ShippingController::class)->except(['index', 'create', 'show', 'edit']);
            });

            // Work Order
            Route::prefix('work-order')->name('work-order.')->group(function () {
                Route::get('/', [ManageProductsController::class, 'index'])->name('index');
            });

            // Users & Sales
            Route::prefix('users-sales')->name('users-sales.')->group(function () {
                Route::get('/', [ManageUsersSalesController::class, 'index'])->name('index');
                Route::get('/fetch-users', [ManageUsersSalesController::class, 'fetchUsers'])->name('fetch-users');
                Route::get('/fetch-sales', [ManageUsersSalesController::class, 'fetchSales'])->name('fetch-sales');
                Route::resource('users', UserController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('sales', SalesController::class)->except(['index', 'create', 'show', 'edit']);
            });
        });
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        // Dashboard
        Route::get('dashboard', fn() => view('pages.admin.dashboard'))->name('dashboard');

        // Orders
        Route::resource('orders', OrderController::class);
        Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('orders/{order}/invoice/download', [OrderController::class, 'downloadInvoice'])->name('orders.invoice.download');

        // Payments
        Route::post('payments', [\App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
        Route::delete('payments/{payment}', [\App\Http\Controllers\PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::get('invoices/{invoice}/payments', [\App\Http\Controllers\PaymentController::class, 'getPaymentsByInvoice'])->name('invoices.payments');

        Route::get('delivery-orders', fn() => view('pages.admin.delivery-orders'))->name('delivery-orders');
        Route::get('work-orders', fn() => view('pages.admin.work-orders'))->name('work-orders');
        Route::get('payment-history', [\App\Http\Controllers\PaymentHistoryController::class, 'index'])->name('payment-history');

        // Customers
        Route::resource('customers', CustomerController::class)->except(['show']);
        
        // API for cascading dropdowns
        Route::get('customers/api/cities/{provinceId}', [CustomerController::class, 'getCities'])->name('customers.api.cities');
        Route::get('customers/api/districts/{cityId}', [CustomerController::class, 'getDistricts'])->name('customers.api.districts');
        Route::get('customers/api/villages/{districtId}', [CustomerController::class, 'getVillages'])->name('customers.api.villages');
    });

    /* ---------- PROJECT MANAGER ---------- */
    Route::prefix('pm')->name('pm.')->middleware('role:pm')->group(function () {
        Route::get('dashboard', fn() => view('pages.pm.dashboard'))->name('dashboard');
        
        // Manage Task
        Route::get('manage-task', [ManageTaskController::class, 'index'])->name('manage-task');
        Route::post('manage-task/update-stage', [ManageTaskController::class, 'updateStage'])->name('manage-task.update-stage');
        Route::post('manage-task/update-stage-status', [ManageTaskController::class, 'updateStageStatus'])->name('manage-task.update-stage-status');
    });

    /* ---------- KARYAWAN ---------- */
    Route::prefix('karyawan')->name('karyawan.')->middleware('role:karyawan,admin,pm')->group(function () {
        Route::get('dashboard', fn() => view('pages.karyawan.dashboard'))->name('dashboard');
        Route::get('task', [App\Http\Controllers\Karyawan\TaskController::class, 'index'])->name('task');
        Route::post('task/mark-done', [App\Http\Controllers\Karyawan\TaskController::class, 'markAsDone'])->name('task.mark-done');
    });

    /* ---------- ALL ROLE ---------- */
    Route::get('highlights', fn() => view('pages.highlights'))->name('highlights');
    Route::get('calendar', fn() => view('pages.calendar'))->name('calendar');
    Route::get('profile', fn() => view('pages.profile'))->name('profile');
});

/* ================= TEST ROUTE ================= */
Route::get('/test', fn() => 'Multi-role authentication system is working!');
