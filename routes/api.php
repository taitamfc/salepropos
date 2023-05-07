<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\AdjustmentController;
use App\Http\Controllers\Api\ReturnSaleController;
use App\Http\Controllers\Api\ReturnPurchaseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\GeneralSettingController;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('home',[HomeController::class,'index']);

Route::get('general_settings',[GeneralSettingController::class,'index']);
Route::post('general_settings',[GeneralSettingController::class,'store']);
Route::get('report/profitLoss',[ReportController::class,'profitLoss']);

Route::post('products/processImport',[ProductController::class,'processImport']);
Route::put('products/changeStatus/{id}',[ProductController::class,'changeStatus']);
Route::apiResource('products',Api\ProductController::class);

Route::put('categories/changeStatus/{id}',[CategoryController::class,'changeStatus']);
Route::apiResource('categories',Api\CategoryController::class);

Route::put('brands/changeStatus/{id}',[BrandController::class,'changeStatus']);
Route::apiResource('brands',Api\BrandController::class);

Route::put('purchases/changeStatus/{id}',[PurchaseController::class,'changeStatus']);
Route::get('purchases/allDue',[PurchaseController::class,'allDue']);
Route::get('purchases/getPayments/{id}',[PurchaseController::class,'getPayments']);
Route::put('purchases/storePayment/{id}',[PurchaseController::class,'storePayment']);
Route::apiResource('purchases',Api\PurchaseController::class);

Route::put('unit/changeStatus/{id}',[UnitController::class,'changeStatus']);
Route::apiResource('unit',Api\UnitController::class);

Route::put('warehouse/changeStatus/{id}',[WarehouseController::class,'changeStatus']);
Route::apiResource('warehouse',Api\WarehouseController::class);

Route::put('supplier/changeStatus/{id}',[SupplierController::class,'changeStatus']);
Route::apiResource('supplier',Api\SupplierController::class);

Route::put('accounts/changeStatus/{id}',[AccountController::class,'changeStatus']);
Route::apiResource('accounts',Api\AccountController::class);

Route::put('transfers/changeStatus/{id}',[TransferController::class,'changeStatus']);
Route::apiResource('transfers',Api\TransferController::class);

Route::put('adjustment/changeStatus/{id}',[AdjustmentController::class,'changeStatus']);
Route::apiResource('adjustment',Api\AdjustmentController::class);

Route::put('sales/changeStatus/{id}',[SaleController::class,'changeStatus']);
Route::get('sales/allDue',[SaleController::class,'allDue']);
Route::get('sales/getPayments/{id}',[SaleController::class,'getPayments']);
Route::put('sales/storePayment/{id}',[SaleController::class,'storePayment']);
Route::apiResource('sales',Api\SaleController::class);

Route::put('return-sales/changeStatus/{id}',[ReturnSaleController::class,'changeStatus']);
Route::apiResource('return-sales',Api\ReturnSaleController::class);

Route::put('return-purchases/changeStatus/{id}',[ReturnPurchaseController::class,'changeStatus']);
Route::apiResource('return-purchases',Api\ReturnPurchaseController::class);
