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

Route::get('auth/install', 'Auth\ShopifyController@installShop')->name('auth.install');
Route::get('auth/callback', 'Auth\ShopifyController@processOAuthResultRedirect');
Route::post('webhooks/uninstalled', 'Auth\ShopifyController@handleAppUninstallation')->name('webhook.uninstalled');
//Route::post('webhooks/inventory_items/{slug}', 'InventoryController@handleInventoryItems')->name('webhook.inventory_items');
Route::post('webhooks/products/{slug}', 'ProductController@handleProducts')->name('webhook.products');
Route::post('webhooks/orders/{slug}', 'OrderController@handleOrders')->name('webhook.orders');
Route::post('customers/redact', 'OrderController@orderRedact');
Route::post('shop/redact', 'Auth\ShopifyController@shopRedact');
Route::get('webhooks/order/{id}/{no}/{token}', 'OrderController@updateOrderStatus');
Route::get('webhooks/fulfillment/locations/{storeId}/{token}', 'SettingController@getFulfillmentLocations');
Route::get('webhooks/inventory/connect/{storeId}/{token}/{locationId}/{product_id}', 'ProductController@connectInventory');
Route::get('webhooks/inventory/set/{storeId}/{token}/{locationId}/{product_id}/{qnty}', 'ProductController@setInventory');
Route::get('webhooks/inventory/adjust/{storeId}/{token}/{locationId}/{product_id}/{qnty}', 'ProductController@adjustInventory');

Route::get('auth/check_webhook/{id}', 'Auth\ShopifyController@getWebhooks');