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

/* Setup CORS */
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Accept-Encoding, Content-Type, Accept, Access-Control-Request-Method, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, PATCH, DELETE");


Route::group(['middleware' => 'api', 'namespace' => 'Api'], function() {
    Route::group(['prefix' => 'auth'], function() {
        Route::post('/login', 'AuthController@login');
        Route::post('/logout', 'AuthController@logout');
    });

    Route::group(['prefix' => 'notifications'], function() {
        Route::get('/', 'NotificationController@getNotifications');
        Route::patch('/{id}', 'NotificationController@SeeNotification');
    });

    Route::group(['prefix' => 'chats'], function() {
        Route::get('/', 'ChatController@getChats');
        Route::get('/{id}', 'ChatController@getConversation');
    });

    Route::group(['prefix' => 'farms'], function() {
        Route::get('/', 'FarmController@getFarms');
        Route::post('/', 'FarmController@addFarm');
        Route::get('/{id}', 'FarmController@getFarm');
        Route::put('/{id}', 'FarmController@updateFarm');
        Route::delete('/{id}', 'FarmController@deleteFarm');
    });

    Route::group(['prefix' => 'orders'], function() {
        // Route::get('/requests/{product_id}', 'OrderController@getRequests');
        // Route::post('/{id}', 'OrderController@requestItem');
        // Route::delete('/requests/{swinecart_id}', 'OrderController@deleteRequest');

        Route::get('/', 'OrderController@getOrders');
        Route::get('/{order_id}', 'OrderController@getOrderDetails');

        // Route::post('/{swinecart_id}/reserve', 'OrderController@reserveProduct');
        // Route::put('/{swinecart_id}/send', 'OrderController@sendProduct');
        // Route::put('/{swinecart_id}/confirm', 'OrderController@confirmSold');
        // Route::delete('/{swinecart_id}/cancel', 'OrderController@cancelTransaction');
    });

    Route::group(['prefix' => 'provinces'], function() {
        Route::get('/', 'ProvinceController@getProvinces');
    });

    Route::group(['prefix' => 'profile'], function() {
        Route::get('/', 'ProfileController@getProfile');
        Route::put('/', 'ProfileController@editProfile');
    });

    Route::group(['namespace' => 'Breeder', 'prefix' => 'breeder'], function() {
        Route::group(['prefix' => 'products'], function() {
            Route::get('/', 'ProductController@getProducts');
            Route::get('/{id}', 'ProductController@getProduct');
            Route::get('/{id}/details', 'ProductController@getProduct');
            Route::post('/', 'ProductController@addProduct');
            Route::put('/{id}', 'ProductController@updateProduct');
            Route::delete('/{id}', 'ProductController@deleteProduct');
            Route::patch('/{id}/status', 'ProductController@toggleProductVisibility');

            Route::get('/{id}/media', 'ProductController@getMedia');
            Route::post('/{id}/media', 'ProductController@addMedia');
            Route::delete('/{id}/media', 'ProductController@deleteMedia');
            Route::patch('/{id}/media', 'ProductController@setPrimaryPicture');
        });

        Route::group(['prefix' => 'dashboard'], function() {
            Route::get('/stats', 'DashboardController@getDashBoardStats');
            Route::get('/ratings', 'DashboardController@getRatings');
            Route::get('/reviews', 'DashboardController@getReviews');
        });

        Route::group(['prefix' => 'orders'], function() {
            Route::get('/{product_id}/requests', 'OrderController@getRequests');
            Route::delete('/requests/{swinecart_id}', 'OrderController@deleteRequest');

            Route::get('/', 'OrderController@getOrders');
            Route::get('/{order_id}', 'OrderController@getOrderDetails');

            Route::post('/{swinecart_id}/reserve', 'OrderController@reserveProduct');
            Route::put('/{swinecart_id}/send', 'OrderController@sendProduct');
            Route::put('/{swinecart_id}/confirm', 'OrderController@confirmSold');
            Route::delete('/{swinecart_id}/cancel', 'OrderController@cancelTransaction');
        });
    });

    Route::group(['namespace' => 'Customer', 'prefix' => 'customer'], function() {
        Route::group(['prefix' => 'shop'], function() {
            Route::get('/products', 'ShopController@getProducts');
            Route::get('/filters', 'ShopController@getFilterOptions');
        });

        Route::group(['prefix' => 'cart'], function() {
            Route::get('/items', 'CartController@getItems');
            Route::post('/items/{id}', 'CartController@addItem');
            Route::delete('/items/{id}', 'CartController@deleteItem');
        });

        Route::group(['prefix' => 'orders'], function() {
            Route::get('/history', 'OrderController@getHistory');
            Route::post('/reviews/{id}', 'OrderController@reviewBreeder');

            Route::get('/', 'OrderController@getOrders');
            Route::get('/{id}', 'OrderController@getOrder');

            Route::post('/{id}', 'OrderController@requestItem');
        });
    });

});
