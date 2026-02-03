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

use App\Http\Controllers\Main\ManageWorkOrderDataController;
use App\Http\Controllers\OrderReportController;
use App\Http\Controllers\MaterialReportController;
use App\Http\Controllers\SupportPartnerReportController;
use App\Http\Controllers\CuttingPatternController;
use App\Http\Controllers\ChainClothController;
use App\Http\Controllers\RibSizeController;
use App\Http\Controllers\PrintInkController;
use App\Http\Controllers\FinishingController;
use App\Http\Controllers\NeckOverdeckController;
use App\Http\Controllers\UnderarmOverdeckController;
use App\Http\Controllers\SideSplitController;
use App\Http\Controllers\SewingLabelController;
use App\Http\Controllers\PlasticPackingController;
use App\Http\Controllers\StickerController;

use App\Http\Controllers\Main\ManageUsersSalesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ManageTaskController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\Finance;

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
            case 'finance':
                return redirect()->route('finance.dashboard');
            case 'pm':
                return redirect()->route('pm.dashboard');
            case 'employee':
                return redirect()->route('employee.dashboard');
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
        Route::get('dashboard', [\App\Http\Controllers\Owner\DashboardController::class, 'index'])->name('dashboard');

        // Payment History - Owner can see and approve/reject
        Route::get('payment-history', [\App\Http\Controllers\PaymentHistoryController::class, 'index'])->name('payment-history');
        Route::patch('payments/{payment}/approve', [\App\Http\Controllers\PaymentController::class, 'approve'])->name('payments.approve');
        Route::patch('payments/{payment}/reject', [\App\Http\Controllers\PaymentController::class, 'reject'])->name('payments.reject');
        Route::get('payments/pending-count', [\App\Http\Controllers\PaymentController::class, 'getPendingCount'])->name('payments.pending-count');
        Route::get('payments/pending-list', [\App\Http\Controllers\PaymentController::class, 'getPendingList'])->name('payments.pending-list');

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
            });

            // Work Order
            Route::prefix('work-orders')->name('work-orders.')->group(function () {
                Route::get('/', [ManageWorkOrderDataController::class, 'index'])->name('index');
                Route::resource('cutting-patterns', CuttingPatternController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('chain-cloths', ChainClothController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('rib-sizes', RibSizeController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('print-inks', PrintInkController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('finishings', FinishingController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('neck-overdecks', NeckOverdeckController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('underarm-overdecks', UnderarmOverdeckController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('side-splits', SideSplitController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('sewing-labels', SewingLabelController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('plastic-packings', PlasticPackingController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('stickers', StickerController::class)->except(['index', 'create', 'show', 'edit']);
            });

            // Finance Data Management
            Route::prefix('finance')->name('finance.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Main\ManageFinanceDataController::class, 'index'])->name('index');
                Route::resource('material-suppliers', \App\Http\Controllers\MaterialSupplierController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('support-partners', \App\Http\Controllers\SupportPartnerController::class)->except(['index', 'create', 'show', 'edit']);
                Route::resource('fix-cost-lists', \App\Http\Controllers\FixCostListController::class)->except(['index', 'create', 'show', 'edit']);
            });

            // Users Management
            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::post('/', [UserController::class, 'store'])->name('store');
                Route::put('/{user}', [UserController::class, 'update'])->name('update');
                Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            });

            // Sales Management
            Route::prefix('sales')->name('sales.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Main\SalesController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Main\SalesController::class, 'store'])->name('store');
                Route::put('/{sale}', [\App\Http\Controllers\Main\SalesController::class, 'update'])->name('update');
                Route::delete('/{sale}', [\App\Http\Controllers\Main\SalesController::class, 'destroy'])->name('destroy');
            });

            // User Profile Management
            Route::prefix('user-profile')->name('user-profile.')->group(function () {
                Route::get('/', [\App\Http\Controllers\UserProfileController::class, 'index'])->name('index');
                Route::put('/{user}', [\App\Http\Controllers\UserProfileController::class, 'update'])->name('update');
            });
        });
        
        // AJAX endpoints for dashboard charts
        Route::prefix('dashboard/chart')->name('dashboard.chart.')->group(function () {
            Route::get('order-trend', [\App\Http\Controllers\Owner\DashboardController::class, 'getOrderTrendData'])->name('order-trend');
            Route::get('product-sales', [\App\Http\Controllers\Owner\DashboardController::class, 'getProductSalesData'])->name('product-sales');
            Route::get('customer-trend', [\App\Http\Controllers\Owner\DashboardController::class, 'getCustomerTrendData'])->name('customer-trend');
            Route::get('customer-province', [\App\Http\Controllers\Owner\DashboardController::class, 'getCustomerProvinceData'])->name('customer-province');
        });
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        // Dashboard
        Route::get('dashboard', fn() => view('pages.admin.dashboard'))->name('dashboard');

        // Work Orders (Admin only)
        Route::get('work-orders', [\App\Http\Controllers\WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('work-orders/{order}/manage', [\App\Http\Controllers\WorkOrderController::class, 'manage'])->name('work-orders.manage');
        Route::post('work-orders', [\App\Http\Controllers\WorkOrderController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('work-orders.store');
        Route::put('work-orders/{workOrder}', [\App\Http\Controllers\WorkOrderController::class, 'update'])
            ->middleware('throttle:10,1')
            ->name('work-orders.update');
        Route::get('work-orders/{order}/finalize', [\App\Http\Controllers\WorkOrderController::class, 'finalize'])->name('work-orders.finalize');

        // Customers (Admin only)
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::resource('customers', CustomerController::class)->except(['show']);
    });

    // Admin routes accessible by both admin and finance roles
    Route::prefix('admin')->name('admin.')->middleware('role:admin,finance')->group(function () {
        // Orders
        Route::resource('orders', OrderController::class);
        Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::patch('orders/{order}/move-to-shipping', [OrderController::class, 'moveToShipping'])->name('orders.move-to-shipping');
        Route::patch('orders/{order}/move-to-report', [OrderController::class, 'moveToReport'])->name('orders.move-to-report');
        Route::get('orders/{order}/invoice/download', [OrderController::class, 'downloadInvoice'])->name('orders.invoice.download');

        // Payments
        Route::post('payments', [\App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
        Route::delete('payments/{payment}', [\App\Http\Controllers\PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::get('invoices/{invoice}/payments', [\App\Http\Controllers\PaymentController::class, 'getPaymentsByInvoice'])->name('invoices.payments');

        // Shipping Orders
        Route::get('shipping-orders', [\App\Http\Controllers\ShippingOrderController::class, 'index'])->name('shipping-orders');
        
        // Report Orders
        Route::get('report-orders', [OrderReportController::class, 'index'])->name('report-orders.index');
        Route::patch('report-orders/{orderReport}/toggle-lock', [OrderReportController::class, 'toggleLock'])->name('report-orders.toggle-lock');
        Route::delete('report-orders/{orderReport}', [OrderReportController::class, 'destroy'])->name('report-orders.destroy');
        
        // Payment History
        Route::get('payment-history', [\App\Http\Controllers\PaymentHistoryController::class, 'index'])->name('payment-history');
        
        // API for cascading dropdowns
        Route::get('customers/api/provinces', [CustomerController::class, 'getProvinces'])->name('customers.api.provinces');
        Route::get('customers/api/cities/{provinceId}', [CustomerController::class, 'getCities'])->name('customers.api.cities');
        Route::get('customers/api/districts/{cityId}', [CustomerController::class, 'getDistricts'])->name('customers.api.districts');
        Route::get('customers/api/villages/{districtId}', [CustomerController::class, 'getVillages'])->name('customers.api.villages');
    });

    /* ---------- FINANCE ---------- */
    Route::prefix('finance')->name('finance.')->middleware('role:finance,owner')->group(function () {
        // Dashboard
        Route::get('dashboard', fn() => view('pages.finance.dashboard'))->name('dashboard');

        // Report Routes
        Route::prefix('report')->name('report.')->group(function () {
            Route::get('order-list', [OrderReportController::class, 'index'])->name('order-list');
            Route::post('order-list/toggle-period-lock', [OrderReportController::class, 'togglePeriodLock'])->name('order-list.toggle-period-lock');
            Route::patch('order-list/{orderReport}/toggle-lock', [OrderReportController::class, 'toggleLock'])->name('order-list.toggle-lock');
            Route::delete('order-list/{orderReport}', [OrderReportController::class, 'destroy'])->name('order-list.destroy');
            Route::get('material', [MaterialReportController::class, 'index'])->name('material');
            Route::get('material/check-period-status', [MaterialReportController::class, 'checkPeriodStatus'])->name('material.check-period-status');
            Route::get('material/get-available-orders', [MaterialReportController::class, 'getAvailableOrders'])->name('material.get-available-orders');
            Route::get('material/get-suppliers', [MaterialReportController::class, 'getSuppliers'])->name('material.get-suppliers');
            Route::get('material/get-order-report/{orderReport}', [MaterialReportController::class, 'getOrderReport'])->name('material.get-order-report');
            Route::post('material/store', [MaterialReportController::class, 'store'])->name('material.store');
            Route::post('material/store-extra', [MaterialReportController::class, 'storeExtra'])->name('material.store-extra');
            Route::put('material/{materialReport}', [MaterialReportController::class, 'update'])->name('material.update');
            Route::delete('material/{materialReport}', [MaterialReportController::class, 'destroy'])->name('material.destroy');
            Route::get('material/{materialReport}/image', [MaterialReportController::class, 'serveProofImage'])->name('material.serve-image');
            
            Route::get('support-partner', [\App\Http\Controllers\SupportPartnerReportController::class, 'index'])->name('support-partner');
            Route::get('support-partner/check-period-status', [\App\Http\Controllers\SupportPartnerReportController::class, 'checkPeriodStatus'])->name('support-partner.check-period-status');
            Route::get('support-partner/get-available-orders', [\App\Http\Controllers\SupportPartnerReportController::class, 'getAvailableOrders'])->name('support-partner.get-available-orders');
            Route::get('support-partner/get-partners', [\App\Http\Controllers\SupportPartnerReportController::class, 'getPartners'])->name('support-partner.get-partners');
            Route::get('support-partner/get-order-report/{orderReport}', [\App\Http\Controllers\SupportPartnerReportController::class, 'getOrderReport'])->name('support-partner.get-order-report');
            Route::post('support-partner/store', [\App\Http\Controllers\SupportPartnerReportController::class, 'store'])->name('support-partner.store');
            Route::post('support-partner/store-extra', [\App\Http\Controllers\SupportPartnerReportController::class, 'storeExtra'])->name('support-partner.store-extra');
            Route::put('support-partner/{partnerReport}', [\App\Http\Controllers\SupportPartnerReportController::class, 'update'])->name('support-partner.update');
            Route::delete('support-partner/{partnerReport}', [\App\Http\Controllers\SupportPartnerReportController::class, 'destroy'])->name('support-partner.destroy');
            Route::get('support-partner/{partnerReport}/image', [\App\Http\Controllers\SupportPartnerReportController::class, 'serveImage'])->name('support-partner.serve-image');

            Route::get('operational', fn() => view('pages.finance.report.operational'))->name('operational');
            Route::get('salary', fn() => view('pages.finance.report.salary'))->name('salary');
        });

        // Internal Transfer
        Route::get('internal-transfer', [Finance\InternalTransferController::class, 'index'])->name('internal-transfer');
        Route::post('internal-transfer', [Finance\InternalTransferController::class, 'store'])->name('internal-transfer.store');
        Route::get('internal-transfer/{transfer}/image', [Finance\InternalTransferController::class, 'serveImage'])->name('internal-transfer.serve-image');
        
        // Loan Capital
        Route::get('loan-capital', [Finance\LoanCapitalController::class, 'index'])->name('loan-capital');
        Route::get('loan-capital/repayment-history', [Finance\LoanCapitalController::class, 'repaymentHistory'])->name('loan-capital.repayment-history');
        Route::get('balance/find-by-period', [Finance\LoanCapitalController::class, 'findBalanceByPeriod'])->name('balance.find-by-period');
        Route::post('loan-capital', [Finance\LoanCapitalController::class, 'store'])->name('loan-capital.store');
        Route::put('loan-capital/{loanCapital}', [Finance\LoanCapitalController::class, 'update'])->name('loan-capital.update');
        Route::post('loan-capital/{loanCapital}/repayment', [Finance\LoanCapitalController::class, 'storeRepayment'])->name('loan-capital.repayment');
        Route::get('loan-capital/{loan}/image', [Finance\LoanCapitalController::class, 'serveImage'])->name('loan-capital.serve-image');
        Route::get('loan-capital/repayment/{repayment}/image', [Finance\LoanCapitalController::class, 'serveRepaymentImage'])->name('loan-capital.serve-repayment-image');
    });

    /* ---------- PROJECT MANAGER ---------- */
    Route::prefix('pm')->name('pm.')->middleware('role:pm,admin,owner,finance')->group(function () {
        Route::get('dashboard', fn() => view('pages.pm.dashboard'))->name('dashboard');
        
        // Manage Task
        Route::get('manage-task', [ManageTaskController::class, 'index'])->name('manage-task');
        Route::post('manage-task/update-stage', [ManageTaskController::class, 'updateStage'])->name('manage-task.update-stage');
        Route::post('manage-task/update-stage-status', [ManageTaskController::class, 'updateStageStatus'])->name('manage-task.update-stage-status');
    });

    /* ---------- EMPLOYEE ---------- */
    Route::prefix('employee')->name('employee.')->middleware('role:employee,admin,pm,finance')->group(function () {
        Route::get('dashboard', fn() => view('pages.employee.dashboard'))->name('dashboard');
        Route::get('task', [App\Http\Controllers\Employee\TaskController::class, 'index'])->name('task');
        Route::post('task/mark-done', [App\Http\Controllers\Employee\TaskController::class, 'markAsDone'])->name('task.mark-done');
        Route::get('task/work-order/{order}', [App\Http\Controllers\Employee\TaskController::class, 'viewWorkOrder'])->name('task.work-order');
    });

    /* ---------- SHARED IMAGE ROUTES (ALL AUTHENTICATED USERS) ---------- */
    // These routes are accessible by all authenticated users (owner, admin, pm, employee)
    // Controllers will handle authorization internally
    
    // Work Order PDF Download (ALL ROLES)
    Route::get('admin/work-orders/{workOrder}/download-pdf', [\App\Http\Controllers\WorkOrderController::class, 'downloadPdf'])->name('admin.work-orders.download-pdf');
    
    // Payment Images
    Route::get('payments/{payment}/image', [\App\Http\Controllers\PaymentController::class, 'serveImage'])->name('payments.serve-image');
    
    // Order Images
    Route::get('orders/{order}/image', [OrderController::class, 'serveOrderImage'])->name('orders.serve-image');
    
    // Work Order Images - USING MODEL BINDING
    Route::get('work-orders/{workOrder}/mockup-image', [\App\Http\Controllers\WorkOrderController::class, 'serveMockupImage'])->name('work-orders.serve-mockup-image');
    Route::get('work-orders/cutting/{cutting}/image', [\App\Http\Controllers\WorkOrderController::class, 'serveCuttingImage'])->name('work-orders.serve-cutting-image');
    Route::get('work-orders/printing/{printing}/image', [\App\Http\Controllers\WorkOrderController::class, 'servePrintingImage'])->name('work-orders.serve-printing-image');
    Route::get('work-orders/placement/{placement}/image', [\App\Http\Controllers\WorkOrderController::class, 'servePlacementImage'])->name('work-orders.serve-placement-image');
    Route::get('work-orders/sewing/{sewing}/image', [\App\Http\Controllers\WorkOrderController::class, 'serveSewingImage'])->name('work-orders.serve-sewing-image');
    Route::get('work-orders/packing/{packing}/image', [\App\Http\Controllers\WorkOrderController::class, 'servePackingImage'])->name('work-orders.serve-packing-image');
    
    // Customer Location API (used by employee view work order)
    Route::get('customers/{customer}/location', [OrderController::class, 'getCustomerLocation'])->name('customers.location');

    /* ---------- ALL ROLE ---------- */
    Route::get('highlights', [\App\Http\Controllers\HighlightController::class, 'index'])->name('highlights');
    Route::get('calendar', [\App\Http\Controllers\CalendarController::class, 'index'])->name('calendar');
    Route::get('profile', fn() => view('pages.profile'))->name('profile');
});

/* ================= TEST ROUTE ================= */
Route::get('/test', fn() => 'Multi-role authentication system is working!');
