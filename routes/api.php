<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\Cors;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['middleware' => ['cors'], 'prefix'=> 'auth'],function(){
    Route::post('/register', 'Auth\Greeter@register');
    Route::get('/login', 'Auth\Greeter@login');
    Route::post('/forgot-password', 'Auth\Greeter@forgotPassword');
    Route::post('/reset-password', 'Auth\Greeter@resetPassword');
    Route::post('/request-new-token', 'Auth\Greeter@requestNewToken');
    Route::post('/check-email-otp', 'Auth\Greeter@checkEmailOTP');
    Route::post('/check-phone-otp', 'Auth\Greeter@checkPhoneOTP');

    Route::get('/admin/login', 'Auth\Greeter@adminLogin');
    Route::post('/admin/forgot-password', 'Auth\Greeter@adminForgotPassword');
    Route::post('/admin/reset-password', 'Auth\Greeter@adminResetPassword');
});

// Route::post('/register', 'Auth\RegisterController@register');
Route::get('/confirm-email', 'Auth\RegisterController@confirmEmail');
Route::get('/checker', 'Auth\RegisterController@checker');

Route::group(['middleware' => ['cors'], 'prefix'=> 'consumers'],function(){
    Route::post('/db-migration', 'Consumers\Consumers@dbMigration');
});

Route::group(['middleware' => ['cors'], 'prefix'=> 'admin'],function(){
    Route::get('/admin', 'Admin\Admin@admin');
    Route::get('/consumers', 'Admin\Admin@consumers');
    Route::get('/activity-log', 'Admin\Admin@activityLog');
    Route::get('/return-and-cancellation', 'Admin\Admin@returnAndCancellation');
    Route::get('/products', 'Admin\Admin@products');
    Route::get('/get-small-orders', 'Admin\Admin@getSmallOrders');
    Route::get('/get-orders', 'Admin\Admin@getOrders');
    Route::get('/feedback', 'Admin\Admin@feedback');
    Route::post('/update-profile', 'Admin\Admin@updateProfile');

    Route::post('/activate-consumer', 'Admin\Admin@activateConsumer');
    Route::get('/dashboard-consumers', 'Admin\Admin@dashboardConsumers');
    Route::post('/delete-consumer', 'Admin\Admin@deleteConsumer');
    Route::get('/get-consumers-small-profile', 'Admin\Admin@getConsumersSmallProfile');
    Route::post('/suspend-consumer', 'Admin\Admin@suspendConsumer');

});

Route::post('/early-access', 'Consumers\Consumers@earlyAccess');
Route::post('/testing', 'Consumers\Consumers@testing');