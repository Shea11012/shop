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
Route::get('products/{product}','ProductController@show')->name('products.show')->where('product','\d+');
Route::get('test','PagesController@root');


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
    Route::get('products/favorites','ProductController@favorites')->name('products.favorites');

    Route::post('cart','CartController@add')->name('cart.add');
    Route::get('cart','CartController@index')->name('cart.index');
    Route::delete('cart/{sku}','CartController@remove')->name('cart.remove');

    Route::post('orders','OrdersController@store')->name('orders.store');
    Route::get('orders','OrdersController@index')->name('orders.index');
    Route::get('orders/{order}','OrdersController@show')->name('orders.show');
    Route::post('orders/{order}/received','OrdersController@received')->name('orders.received');
    Route::get('orders/{order}/review','OrdersController@review')->name('orders.review.show');
    Route::post('orders/{order}/review','OrdersController@sendReview')->name('orders.review.store');
    Route::post('orders/{order}/apply_refund','OrdersController@applyRefund')->name('orders.apply_refund');
    Route::post('crowdfunding_orders','OrdersController@crowdfunding')->name('crowdfunding_orders.store');

    Route::get('payment/{order}/alipay','PaymentController@payByAlipay')->name('payment.alipay');
    Route::get('payment/alipay/return','PaymentController@alipayReturn')->name('payment.alipay.return');
    Route::get('payment/{order}/wechat','PaymentController@payByWechat')->name('payment.wechat');

    Route::get('coupon_codes/{code}','CouponCodesController@show')->name('coupon_codes.show');
    Route::post('payment/{order}/installment','PaymentController@payByInstallment')->name('payment.installment');

    Route::get('installments','InstallmentsController@index')->name('installments.index');
    Route::get('installments/{installment}','InstallmentsController@show')->name('installments.show');
    Route::get('installments/{installment}/alipay','InstallmentsController@payByAlipay')->name('installments.alipay');
    Route::get('installments/alipay/return','InstallmentsController@alipayReturn')->name('installments.alipay.return');
});

Route::post('payment/alipay/notify','PaymentController@alipayNotify')->name('payment.alipay.notify');
Route::post('payment/alipay/notify','PaymentController@wechatNotify')->name('payment.wechat.notify');
Route::post('payment/wechat/refund_notify','PaymentController@wechatRefundNotify')->name('payment.wechat.refund_notify');
Route::post('installments/alipay/notify','InstallmentsController@alipayNotify')->name('installments.alipay.notify');
Route::post('installments/wechat/refund_notify','InstallmentsController@wechatRefundNotify')->name('installments.wechat.refund_notify');

