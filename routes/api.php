<?php
// routes/api.php

use App\Http\Controllers\Api\V1\Tenant\AccountController;
use App\Http\Controllers\Api\V1\Tenant\EstimateShippingController;
use App\Http\Controllers\Api\V1\Tenant\MagentoCustomerController;
use App\Http\Controllers\Api\V1\StoresController;

use App\Http\Controllers\Api\V1\Tenant\MagentoCategoryController;
use App\Http\Controllers\Api\V1\Tenant\PaymentController;
use App\Http\Controllers\Api\V1\Tenant\ProductController;
use App\Http\Controllers\Api\V1\Tenant\StoreAddressController;
use App\Http\Controllers\Api\V1\Tenant\StoreUserAdminController;
use App\Http\Controllers\Api\V1\Tenant\TenantAuthController;
use App\Http\Controllers\Api\V1\Tenant\TenantController;
use App\Http\Controllers\Api\V1\Tenant\ThemesController;
use App\Http\Controllers\Api\V1\Tenant\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\MagentoSyncController;
use App\Http\Controllers\Api\V1\AdminProductController;
use App\Http\Controllers\Api\V1\Tenant\OrderController;
use App\Http\Controllers\Api\V1\Tenant\CmsPageController;
use App\Http\Controllers\Api\V1\Tenant\CmsBlockController;
use App\Http\Controllers\Api\V1\ProductIngestionController;
use App\Http\Controllers\Api\V1\Tenant\UserAdminController;
use App\Http\Controllers\Api\V1\Tenant\Product\AttributeController;
use App\Http\Controllers\Api\V1\Tenant\Payment\ListPaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth.apikey')->post('/v1/products/ingest', [ProductIngestionController::class, 'store']);


Route::middleware('auth:sanctum')->prefix('v1/admin')->group(function () {
    Route::apiResource('products', AdminProductController::class)->only(['index', 'show', 'update']);
    Route::post('magento/sync', [MagentoSyncController::class, 'sync']);

    Route::post('products/{product}/sync', MagentoSyncController::class);

    Route::post('stores', [StoresController::class, 'create']);
    //   Route::post('stores/sync', [MagentoSyncController::class, 'sync']);
    Route::get('customers/group/{storeGroupId}', [MagentoCustomerController::class, 'getCustomersByStoreGroup']);

    Route::apiResource('tenant/products', ProductController::class);
    Route::apiResource('tenant/orders', OrderController::class);
    Route::apiResource('tenant/cmsBlocks', CmsBlockController::class);
    Route::apiResource('tenant/cmsPages', CmsPageController::class);
    //Route::apiResource('tenant/userAdmin', UserAdminController::class);
    Route::apiResource('tenant/userAdmin', StoreUserAdminController::class);


    #attributes
    Route::get('/tenant/attributes/{attributeCode}', [AttributeController::class, 'show'])->name('attributes.show');
    Route::post('/tenant/attributes', [AttributeController::class, 'store'])->name('attributes.store');

    // Attribute Set / Group Management
    Route::post('/tenant/attribute-sets/groups', [AttributeController::class, 'createGroup'])->name('attribute-sets.groups.create');
    Route::post('/tenant/attribute-sets/assign-attribute', [AttributeController::class, 'assignToSet'])->name('attribute-sets.assign');

    // Product-specific Attribute Update
    Route::put('/tenant/products/{sku}/attributes', [AttributeController::class, 'updateProductAttribute'])->name('products.attributes.update');

    // System Actions
    Route::post('/tenant/reindex', [AttributeController::class, 'reindex'])->name('system.reindex');
    ###################


    Route::get('tenant/paymentlist', [ListPaymentController::class, 'listPaymentMethods']);
});

/*Route::middleware(['detect.guard', 'auth:sanctum', 'permission:themes'])->prefix('v1/admin/tenant')->group(function () {
    Route::apiResource('themes', ThemesController::class);
});*/


Route::middleware('auth:sanctum')->prefix('v1/admin')->group(function () {
    Route::apiResource('tenant/products', ProductController::class);
    Route::prefix('categories')->group(function () {
        Route::get('{id}', [MagentoCategoryController::class, 'getCategory']);
        Route::post('/', [MagentoCategoryController::class, 'createCategory']);
        Route::put('{id}', [MagentoCategoryController::class, 'updateCategory']);
    });

    Route::post('/store_address', [StoreAddressController::class, 'store']);


    Route::prefix('cart/{cartId}')->group(function () {
        Route::post('estimate-shipping', [EstimateShippingController::class, 'estimate']);
        Route::post('set-shipping', [EstimateShippingController::class, 'setShipping']);
    });
    Route::apiResource('/userAdmin', StoreUserAdminController::class);

    Route::get('/accounts/me', [AccountController::class, 'show'])->name('accounts.show');
    Route::post('/transactions/deposit', [TransactionController::class, 'deposit'])->name('transactions.deposit');
    Route::post('/transactions/withdraw', [TransactionController::class, 'withdraw'])->name('transactions.withdraw');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/invoices', [\App\Http\Controllers\Api\V1\Tenant\MagentoInvoiceController::class, 'getInvoices']);

    Route::post('/payment/initiate', [PaymentController::class, 'initiate'])->name('payment.initiate');
    Route::get('/payment/verify/{gateway}/{transaction}', [PaymentController::class, 'verify'])->name('payment.verify');
    Route::get('/tenant/me', function (Request $request) {   return $request->user(); });
    Route::apiResource('themes', ThemesController::class);


});

Route::get('v1/admin/tenant/by-domain', [TenantController::class, 'getTenantByDomain']);
Route::delete('v1/admin/tenant/delete-by-email', [TenantController::class, 'destroyByEmail']);
Route::apiResource('v1/admin/tenant', TenantController::class);


Route::post('v1/admin/tenant/login', [TenantAuthController::class, 'login']);
Route::post('v1/admin/user-admin/login', [\App\Http\Controllers\Api\V1\Tenant\UserAdminAuthController::class, 'login']);
Route::post('v1/admin/tenant/register', [TenantAuthController::class, 'register']);
