<?php

//header('Access-Control-Allow-Origin:  *');
//header('Access-Control-Allow-Methods:  GET');

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */

//Route::get('/', function () {
//    return view('welcome');
//});
Route::get('login', function () {
    return view('welcome');
});
Route::post('login', 'Auth\LoginController@login')->name('login');
Route::get('logout', 'Auth\LoginController@logout')->name('logout');
Route::get('/load/{slug}', 'Auth\ShopifyController@load')->name('load');
   
Route::group(['middleware' => 'auth'], function () {
    Route::post('warehouse/api/setting', 'SettingController@apiPostSetting')->name('warehouse.api.setting');
    Route::post('warehouse/dev/setting', 'SettingController@devPostSetting')->name('warehouse.dev.setting');
    Route::get('warehouse/product/sync', 'ProductController@synchronizeProducts')->name('warehouse.product.sync');
});
Route::get('warehouse/order/test/{user_id}', 'OrderController@checkWebhooks');
Route::group(['middleware' => 'Hmac'], function () {
    Route::get('/dashboard/{slug}', 'Auth\ShopifyController@index')->name('dashboard');
    Route::get('warehouse/setting/{slug}', 'SettingController@warehouseSetting')->name('warehouse.setting');
    Route::get('aunthenticate/{slug}', 'Auth\ShopifyController@storeAuthenticate')->name('authenticate');
    Route::get('warehouse/order/details/{slug}', 'OrderController@orderDetails')->name('warehouse.order.details');
});
Route::group(['prefix' => 'admin_warehouse', 'middleware' => 'IsAdmin'], function () {
    Route::get('/', 'Admin\IndexController@index')->name('admin');
    Route::resource('users', 'Admin\UserController');
    Route::get('customer/login/{id}', 'Admin\IndexController@customerLogin')->name('customer.login');
});


