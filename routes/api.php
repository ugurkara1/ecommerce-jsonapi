<?php

use App\Http\Controllers\AttributesController;
use App\Http\Controllers\AttributeValuesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\OrderAdressesController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderProductController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductImagesController;
use App\Http\Controllers\ProductQrCodesController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InvoicesController;
use App\Models\Brands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    //login/register/verify
    Route::post('register',[AuthController::class,'register']);
    Route::post('login',[AuthController::class,'login'])->name('login');
    Route::post('verify',[AuthController::class,'verifyOtp']);

    Route::post('/super-admin/register', [SuperAdminController::class, 'register'])->middleware('auth:sanctum');
    Route::post('super-admin/updateRoles', [SuperAdminController::class, 'updateUserRoles'])->middleware('auth:sanctum');
    Route::post('super-admin/addRole', [SuperAdminController::class, 'createRole'])->middleware('auth:sanctum');
    //Role and Permission
    Route::post('/assign-role', [RoleController::class, 'assignRole'])->middleware('auth:sanctum');
    Route::post( '/create-permission', [RoleController::class,'createPermissions'])->middleware('auth:sanctum');
    //Brands crud
    Route::middleware('auth:sanctum')->prefix('brands')->group(function () {
        Route::get('/', [BrandController::class, 'index']);
        Route::post('/', [BrandController::class, 'store']);
        Route::get('/{id}', [BrandController::class, 'show']);
        Route::put('/{id}', [BrandController::class, 'update']);
        Route::delete('/{id}', [BrandController::class, 'destroy']);
    });
    //Categories crud
    Route::middleware('auth:sanctum')->prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'create']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });
    Route::middleware('auth:sanctum')->prefix('products')->group(function(){
        Route::get('/',[ProductController::class,'index']);
        Route::get('/{id}',[ProductController::class,'show']);
        Route::post('/',[ProductController::class,'create']);
        Route::put('/{id}',[ProductController::class,'update']);
        Route::delete('/{id}',[ProductController::class,'destroy']);
    });



    //Attributes crud
    Route::middleware('auth:sanctum')->prefix('attributes')->group(function () {
        Route::get('/', [AttributesController::class, 'index']);
        Route::get('/{id}', [AttributesController::class, 'show']);
        Route::post('/', [AttributesController::class, 'store']);
        Route::put('/{id}', [AttributesController::class, 'update']);
        Route::delete('/{id}', [AttributesController::class, 'destroy']);
    });

    //attribute-values crud
    Route::middleware('auth:sanctum')->prefix('attribute-values')->group(function () {
        Route::get('/{id}', [AttributeValuesController::class, 'show']);
        Route::post('/{id}', [AttributeValuesController::class, 'store']);
        Route::put('/{valueId}', [AttributeValuesController::class, 'update']);
        Route::delete('/{valueId}', [AttributeValuesController::class, 'delete']);
        Route::get('/',[AttributeValuesController::class,'index']);
    });
    //products crud
    Route::middleware('auth:sanctum')->prefix('products')->group(function () {
    /*
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);

        product Variants (alternatively, use dedicated prefix "productVariants")

    */
        Route::get('variant', [ProductVariantController::class, 'index']);
        Route::post('/{productId}/variants', [ProductVariantController::class, 'store']);
        Route::get('/show/{productId}/variants/{variantId}', [ProductVariantController::class, 'show']);
        Route::put('/{productId}/variants/{variantId}', [ProductVariantController::class, 'update']);
        Route::delete('/{productId}/variants/{variantId}', [ProductVariantController::class, 'destroy']);
    });
    // Variant Region
    Route::middleware('auth:sanctum')->post('variants/{variantId}/region', [ProductVariantController::class, 'storeVariantRegion']);

    // Product Images & QR Codes
    // -------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/products/{product}/images', [ProductImagesController::class, 'store']);
        Route::delete('/product-images', [ProductImagesController::class, 'destroy']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{productVariantId}/qr-codes', [ProductQrCodesController::class, 'store']);
        Route::delete('/{productVariantId}/qr-codes/{qrCodeId}', [ProductQrCodesController::class, 'destroy']);
    });
    Route::get('/{productId}/qr-codes', [ProductQrCodesController::class, 'index']);


    //discounts crud
    Route::middleware('auth:sanctum')->prefix('discounts')->group(function () {
        Route::post('/', [DiscountController::class, 'create']);
        Route::get('/', [DiscountController::class, 'index']);
        Route::put('/End/{discountId}', [DiscountController::class, 'endDiscount']);
        Route::put('/{discountId}', [DiscountController::class, 'update']);
        Route::delete('/{discountId}', [DiscountController::class, 'delete']);
        Route::get('/{discountId}', [DiscountController::class, 'show']);
    });


    //payment crud
    Route::middleware('auth:sanctum')->prefix('payment')->group(function () {
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::put('/{id}', [PaymentController::class, 'update']);
        Route::delete('/', [PaymentController::class, 'destroy']);
    });




    //CRUD campaigns
    Route::middleware('auth:sanctum')->prefix('campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::get('/{id}', [CampaignController::class, 'show']);
        Route::put('/{id}', [CampaignController::class, 'update']);
        Route::delete('/{id}', [CampaignController::class, 'delete']);
    });
    // Orders & Order Processes
    // -------------------------------
    Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'create']);
        Route::delete('/{id}', [OrderController::class, 'destroy']);
        Route::get('/orderFilter', [OrderController::class, 'filterOrders']);
        //Route::post('/orderPayment/{id}', [OrderController::class, 'paymentOrder']);
        //Route::post('/confirmOrder/{id}', [OrderController::class, 'confirmOrder']);
        //Route::put('/orderPreparing/{id}', [OrderController::class, 'orderPreparing']);
       // Route::put('/orderDelivery/{id}', [OrderController::class, 'orderDelivered']);
        Route::put('/{id}', [OrderController::class, 'updateOrderProcess']);
        //Route::put('update/{id}',[OrderController::class,'update']);
    });
    Route::middleware('auth:sanctum')->prefix('orderAddresses')->group(function(){
        Route::post('/', [OrderAdressesController::class, 'create']);
        Route::put('/{id}', [OrderAdressesController::class, 'update']);
        Route::delete('/{id}', [OrderAdressesController::class, 'delete']);
        Route::get('/', [OrderAdressesController::class, 'index']);
        Route::get('/{id}', [OrderAdressesController::class, 'show']);
    });

    // Order Products
    // -------------------------------
    Route::middleware('auth:sanctum')->prefix('orderProduct')->group(function () {
        Route::post('/', [OrderProductController::class, 'store']);
        Route::put('/{id}', [OrderProductController::class, 'update']);
        Route::get('/', [OrderProductController::class, 'index']);
        Route::get('/{id}', [OrderProductController::class, 'show']);
        Route::delete('/{id}', [OrderProductController::class, 'destroy']);
    });
    //CRUD Invoices
    Route::middleware('auth:sanctum')->prefix('invoices')->group(function () {
        Route::post('/', [InvoicesController::class, 'store']);
        Route::get('/', [InvoicesController::class, 'index']);
        Route::get('/{id}', [InvoicesController::class, 'show']);
        Route::put('/{id}', [InvoicesController::class, 'update']);
        Route::delete('/{id}', [InvoicesController::class, 'destroy']);
        Route::post('/{id}/send-email', [InvoicesController::class, 'sendInvoiceEmail']);
    });

});
Route::prefix('v1/customer')->group(function () {
    Route::post('/register', [CustomerController::class, 'register']);
    Route::post('/login', [CustomerController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/add-profile', [CustomerController::class, 'addProfile']);
        Route::delete('/delete-profile', [CustomerController::class, 'destroyProfiles']);
        Route::get('/profile', [CustomerController::class, 'getProfile']);
        Route::post('/address', [CustomerController::class, 'createAddress']);
        Route::put('/address', [CustomerController::class, 'updateAddress']);
        Route::delete('/address', [CustomerController::class, 'deleteAddress']);
        Route::get('/address', [CustomerController::class, 'index']);
    });
});
