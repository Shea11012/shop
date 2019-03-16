<?php

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

Route::get('/', 'ProductController@index')->name('root');
Route::get('products','ProductController@index')->name('products.index');
Route::get('products/{product}','ProductController@show')->name('products.show');
Route::get('test',function (){
    return $_ENV;
});


Auth::routes(['verify' => true]);

Route::group(['middleware' => ['auth','verified']],function () {
    Route::get('user_address','UserAddressesController@index')->name('user_addresses.index');
    Route::get('user_addresses/create','UserAddressesController@create')->name('user_addresses.create');
    Route::post('user_addresses','UserAddressesController@store')->name('user_addresses.store');
    Route::get('user_addresses/{userAddress}','UserAddressesController@edit')->name('user_addresses.edit');
    Route::put('user_addresses/{userAddress}','UserAddressesController@update')->name('user_addresses.update');
    Route::delete('user_addresses/{userAddress}','UserAddressesController@destroy')->name('user_addresses.destroy');

    Route::post('products/{product}/favorite','ProductController@favor')->name('products.favor');
    Route::delete('products/{product}/favorite','ProductController@disfavor')->name('products.disfavor');
});

