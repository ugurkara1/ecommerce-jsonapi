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
    Route::post('/brands',[BrandController::class,'create'])->middleware('auth:sanctum');
    Route::get('/brands', [BrandController::class,'getBrands'])->middleware('auth:sanctum');
    Route::put('brands/{id}', [BrandController::class,'update'])->middleware('auth:sanctum');
    Route::delete('brands/{id}', [BrandController::class,'destroy'])->middleware('auth:sanctum');
    Route::get('/showbrands', [BrandController::class,'show'])->middleware('auth:sanctum');
    Route::get('/superadmin',[SuperAdminController::class,'index'])->middleware('auth:sanctum');

    //Categories crud
    Route::post('/categories',[CategoryController::class,'create'])->middleware('auth:sanctum');
    Route::delete('/categories/{id}',[CategoryController::class,'destroy'])->middleware('auth:sanctum');
    Route::get('/categories',[CategoryController::class,'getCategory'])->middleware('auth:sanctum');
    Route::put('/categories/{id}',[CategoryController::class,'update'])->middleware('auth:sanctum');
    Route::get('/show/category',[CategoryController::class,'show'])->middleware('auth:sanctum');

    //Attributes crud
    Route::get('/attributes',[AttributesController::class,'index'])->middleware('auth:sanctum');
    Route::post('/attributes',[AttributesController::class,'store'])->middleware('auth:sanctum');
    Route::put('/attributes/{id}',[AttributesController::class,'update'])->middleware('auth:sanctum');
    Route::delete('/attributes/{id}',[AttributesController::class,'destroy'])->middleware('auth:sanctum');

    //attribute-values crud
    Route::get('/attributes/{attribute}/attribute-values', [AttributeValuesController::class, 'getAttrValue'])->middleware('auth:sanctum');
    Route::post('/attributes/{attribute}/attribute-values', [AttributeValuesController::class, 'store'])->middleware('auth:sanctum');
    Route::put('/attributes/{attribute}/attribute-values/{value}', [AttributeValuesController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/attributes/{attribute}/attribute-values/{value}', [AttributeValuesController::class, 'destroy'])->middleware('auth:sanctum');
    Route::get('/attrValue',[AttributeValuesController::class,'index']);

    //products crud
    Route::post('/products', [ProductController::class,'store'])->middleware('auth:sanctum');
    Route::get('/products', [ProductController::class,'index'])->middleware('auth:sanctum');
    Route::get('/products/show/{id}', [ProductController::class,'show'])->middleware('auth:sanctum');
    Route::delete('/products', [ProductController::class,'destroy'])->middleware('auth:sanctum');
    Route::put('/products/{id}', [ProductController::class,'update'])->middleware('auth:sanctum');

    //product-variants crud
    Route::get('/products/{productId}/variants', [ProductVariantController::class, 'index'])->middleware('auth:sanctum');
    Route::post('/products/{productId}/variants', [ProductVariantController::class,'store'])->middleware('auth:sanctum');
    Route::get('/products/show/{productId}/variants/{variantId}', [ProductVariantController::class,'show'])->middleware('auth:sanctum');
    Route::put('/products/{productId}/variants/{variantId}', [ProductVariantController::class,'update'])->middleware('auth:sanctum');
    Route::delete('/products/{productId}/variants/{variantId}', [ProductVariantController::class,'destroy'])->middleware('auth:sanctum');


    //products-images crud
    Route::post('/products/{product}/images', [ProductImagesController::class, 'store'])->middleware('auth:sanctum');
    Route::delete('/product-images', [ProductImagesController::class, 'destroy'])->middleware('auth:sanctum');

    //product-qr-codes crud
    Route::post('/{productVariantId}/qr-codes', [ProductQrCodesController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/{productId}/qr-codes', [ProductQrCodesController::class, 'index']);
    Route::delete('/{productVariantId}/qr-codes/{qrCodeId}', [ProductQrCodesController::class,'destroy'])->middleware('auth:sanctum');


    //discounts crud
    Route::get('/discounts',[DiscountController::class,'index']);
    Route::post('/discounts', [DiscountController::class,'store'])->middleware('auth:sanctum');
    Route::put('/discounts/{discountId}', [DiscountController::class,'update'])->middleware('auth:sanctum');
    Route::put('/discountsEnd/{discountId}', [DiscountController::class,'endDiscount    '])->middleware('endDiscount');
    Route::delete('/discounts/{discountId}', [DiscountController::class,'destroy'])->middleware('auth:sanctum');
    Route::get('/discounts/{discountId}', [DiscountController::class,'show'])->middleware('auth:sanctum');

    //payment crud
    Route::post('/payment',[PaymentController::class,'store'])->middleware('auth:sanctum');
    Route::get('/payment', [PaymentController::class,'index']);
    Route::put('/payment/{id}', [PaymentController::class,'update'])->middleware('auth:sanctum');
    Route::get('/payment/{id}', [PaymentController::class,'show'])->middleware('auth:sanctum');
    Route::delete('/payment',[PaymentController::class,'destroy'])->middleware('auth:sanctum');


    //CRUD campaigns
    Route::get('/campaigns',[CampaignController::class,'index']);
    Route::post('/campaigns',[CampaignController::class,'store'])->middleware('auth:sanctum');
    Route::get('/campaigns/{id}',[CampaignController::class,'show']);
    Route::put('/campaigns/{id}',[CampaignController::class,'update'])->middleware('auth:sanctum');
    Route::delete('/campaigns/{id}',[CampaignController::class,'destroy'])->middleware('auth:sanctum');

    //CRUD orders
    Route::get('/orders',[OrderController::class,'index']);
    Route::post('/orders',[OrderController::class,'store'])->middleware('auth:sanctum');
    Route::put('/orders/{id}',[OrderController::class,'update'])->middleware('auth:sanctum');
    Route::delete('orders/{id}',[OrderController::class,'destroy'])->middleware('auth:sanctum');
    Route::put('/orderStatus/{id}',[OrderController::class,'updateStatus'])->middleware('auth:sanctum');
    Route::get('/orderFilter',[OrderController::class,'OrderFilter']);
    //Sipariş Süreçleri
    Route::post('/orderPayment/{id}',[OrderController::class,'paymentOrder'])->middleware('auth:sanctum'); //Ödeme işlemine geçis
    Route::post('/confirmOrder/{id}',[OrderController::class,'confirmOrder'])->middleware('auth:sanctum'); //Siparis Onay
    Route::put('/orderPreparing/{id}',[OrderController::class,'orderPreparing'])->middleware('auth:sanctum'); //Siparis Hazırlandı Kargoya Teslim
    Route::put('/orderDelivery/{id}',[OrderController::class,'orderDelivered'])->middleware('auth:sanctum'); //Siparis Teslim edildi



    //CRUD orderAddress
    Route::post('/orderAddress',[OrderAdressesController::class,'store'])->middleware('auth:sanctum');
    Route::put('/orderAddress/{id}',[OrderAdressesController::class,'update'])->middleware('auth:sanctum');
    Route::delete('/orderAddress/{id}',[OrderAdressesController::class,'destroy'])->middleware('auth:sanctum');
    Route::get('/orderAddress',[OrderAdressesController::class,'index']);
    Route::get('/orderAddress/{id}',[OrderAdressesController::class,'show']);

    //CRUD OrderProduct
    Route::post('/orderProduct',[OrderProductController::class,'store'])->middleware('auth:sanctum');
    Route::put('/orderProduct/{id}',[OrderProductController::class,'update'])->middleware('auth:sanctum');
    Route::get('/orderProduct',[OrderProductController::class,'index']);
    Route::get('/orderProduct/{id}',[OrderProductController::class,'show']);
    Route::delete('/orderProduct/{id}',[OrderProductController::class,'destroy'])->middleware('auth:sanctum');

    //CRUD Invoices
    Route::post('/invoices',[InvoicesController::class,'store'])->middleware('auth:sanctum');
    Route::get('/invoices',[InvoicesController::class,'index'])->middleware('auth:sanctum');
    Route::get('/invoices/{id}',[InvoicesController::class,'show'])->middleware('auth:sanctum');
    Route::put('/invoices/{id}',[InvoicesController::class,'update'])->middleware('auth:sanctum');
    Route::delete('/invoices/{id}',[InvoicesController::class,'destroy'])->middleware('auth:sanctum');
    Route::post('/invoices/{id}/send-email', [InvoicesController::class, 'sendInvoiceEmail']);


});
Route::prefix('v1')->group(function () {
    Route::prefix('customer')->group(function () {

        Route::post('/register',[CustomerController::class,'register']);
        Route::post('/login',[CustomerController::class,'login']);
        Route::post( '/add-profile',[CustomerController::class,'addProfile'])->middleware('auth:sanctum');
        Route::delete('/delete-profile',[CustomerController::class,'destroyProfiles'])->middleware('auth:sanctum');
        Route::get('/profile',[CustomerController::class,'getProfile'])->middleware('auth:sanctum');


        Route::post('/address',[CustomerController::class,'createAddress'])->middleware('auth:sanctum');
        Route::put('/address',[CustomerController::class,'updateAddress'])->middleware('auth:sanctum');
        Route::delete('/address',[CustomerController::class,'deleteAddress'])->middleware('auth:sanctum');
        Route::get('/address', [CustomerController::class,'index'])->middleware('auth:sanctum');
    });
});
