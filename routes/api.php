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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('products',Api\ProductController::class);
Route::apiResource('categories',Api\CategoryController::class);
Route::apiResource('brands',Api\BrandController::class);
Route::apiResource('purchases',Api\PurchaseController::class);
Route::apiResource('unit',Api\UnitController::class);
Route::apiResource('warehouse',Api\WarehouseController::class);
Route::apiResource('supplier',Api\SupplierController::class);
Route::apiResource('accounts',Api\AccountController::class);
Route::apiResource('transfers',Api\TransferController::class);
Route::apiResource('adjustment',Api\AdjustmentController::class);
Route::apiResource('sales',Api\SaleController::class);
Route::apiResource('return-sales',Api\ReturnSaleController::class);
