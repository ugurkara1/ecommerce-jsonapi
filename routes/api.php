<?php

use App\Http\Controllers\AttributesController;
use App\Http\Controllers\AttributeValuesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\ProductImagesController;
use App\Http\Controllers\ProductQrCodesController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
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
    Route::get('/attributes/{attribute}/attribute-values', [AttributeValuesController::class, 'index'])->middleware('auth:sanctum');
    Route::post('/attributes/{attribute}/attribute-values', [AttributeValuesController::class, 'store'])->middleware('auth:sanctum');
    Route::put('/attributes/{attribute}/attribute-values/{value}', [AttributeValuesController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/attributes/{attribute}/attribute-values/{value}', [AttributeValuesController::class, 'destroy'])->middleware('auth:sanctum');

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
    Route::put('/discountsEnd/{discountId}', [DiscountController::class,''])->middleware('endDiscount');
    Route::delete('/discounts/{discountId}', [DiscountController::class,'destroy'])->middleware('auth:sanctum');
    Route::get('/discounts/{discountId}', [DiscountController::class,'show'])->middleware('auth:sanctum');

});
Route::prefix('v1')->group(function () {
    Route::prefix('customer')->group(function () {

        Route::post('/register',[CustomerController::class,'register']);
        Route::post('/login',[CustomerController::class,'login']);


    });
});
