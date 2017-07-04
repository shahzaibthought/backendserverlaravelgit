<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/shop/show','ShopController@fetchShops');
Route::get('/shop/{shopId}/products','ShopController@fetchProducts');
Route::post('/shop', 'ShopController@createShop');
Route::post('/shop/{shopId}/product', 'ShopController@addProduct');
Route::match(array('put','patch'),'/shop/{shopId}/product/{productId}', 'ShopController@modifyProduct');
Route::delete('/shop/{shopId}/product/{productId}', 'ShopController@deleteProduct');