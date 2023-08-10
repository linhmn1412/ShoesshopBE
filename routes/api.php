<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\BrandController;
use App\Http\Controllers\API\V1\CartItemController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\DiscountController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(['prefix' => 'v1'], function () {

    //auth
    
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');


    //product
    Route::get('/products', [ProductController::class, 'getAllProducts']);
    Route::get('/products/newArrivals', [ProductController::class, 'getProductsNewArrivals']);
    Route::get('/products/bestSellers', [ProductController::class, 'getProductsBestSellers']);
    Route::get('/products/category={category}', [ProductController::class, 'getProductsByCategory']);
    Route::get('/products/brand={brand}', [ProductController::class, 'getProductsByBrand']);
    Route::get('/products/price={p1}-{p2}', [ProductController::class, 'getProductsByPrice']);
    Route::get('/products/sale={sale}', [ProductController::class, 'getProductsSale']);

    Route::get('/product/{id}/image', [ProductController::class, 'getImage']);
    Route::get('/product/{id}', [ProductController::class, 'getProductDetail']);

    //discount 
    Route::get('/discounts', [DiscountController::class, 'index']);

    //category
    Route::get('/categories', [CategoryController::class, 'index']);

    //brand
    Route::get('/brands', [BrandController::class, 'index']);

    //review
    Route::get('/product-{id}/review', [ReviewController::class, 'getReviewsProductById']);

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/getCartItemByUser', [CartItemController::class, 'getCartItemByUser']);
        // manage cart_item
        Route::post('/addToCart', [CartItemController::class, 'addToCart']);
        Route::post('/updateCartItem', [CartItemController::class, 'update']);
        Route::post('/removeCartItem', [CartItemController::class, 'destroy']);

        //manage order
        Route::post('/order/create', [OrderController::class, 'store']);
        Route::get('/getOrderPending', [OrderController::class, 'getOrderPending']);
        Route::get('/getAllOrders', [OrderController::class, 'getAllOrders']);
        Route::put('/order/approve', [OrderController::class, 'approveOrder']);
        Route::put('/order/cancel', [OrderController::class, 'cancelOrder']);
        Route::put('/order/receive', [OrderController::class, 'receiveOrder']);
        Route::get('/getOrdersByUser', [OrderController::class, 'getOrdersByUser']);

        //manage discount
        Route::get('/getAllDiscounts', [DiscountController::class, 'getAllDiscounts']);
        Route::post('/discount/create', [DiscountController::class, 'store']);
        Route::put('/discount/update', [DiscountController::class, 'update']);
        Route::delete('/discount/delete', [DiscountController::class, 'destroy']);

        //manage category
        Route::get('/getAllCategories', [CategoryController::class, 'getAllCategories']);
        Route::post('/category/create', [CategoryController::class, 'store']);
        Route::put('/category/update', [CategoryController::class, 'update']);
        Route::delete('/category/delete', [CategoryController::class, 'destroy']);

        //manage discount
        Route::get('/getAllBrands', [BrandController::class, 'getAllBrands']);
        Route::post('/brand/create', [BrandController::class, 'store']);
        Route::put('/brand/update', [BrandController::class, 'update']);
        Route::delete('/brand/delete', [BrandController::class, 'destroy']);

        //manage products
        Route::get('/getAllProducts', [ProductController::class, 'getAllProductsAdmin']);
        Route::post('/product-create', [ProductController::class, 'createProduct']);
        Route::put('/product-update', [ProductController::class, 'updateProduct']);
        Route::delete('/product-delete', [ProductController::class, 'destroyProduct']);



        //review 
        Route::post('/reviews/create', [ReviewController::class, 'store']);

    });
  
});