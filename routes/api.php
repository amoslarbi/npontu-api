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

Route::group(['middleware' => ['cors'], 'prefix'=> 'auth'],function(){
    Route::post('/register', 'Auth\Greeter@register');
    Route::get('/login', 'Auth\Greeter@login');
    Route::get('/admin/login', 'Auth\Greeter@adminLogin');
});

Route::group(['middleware' => ['cors'], 'prefix'=> 'consumers'],function(){
    Route::post('/db-migration', 'Consumers\Consumers@dbMigration');
});

Route::group(['middleware' => ['cors'], 'prefix'=> 'admin'],function(){
    Route::get('/consumers', 'Admin\Admin@consumers');
    Route::post('/activate-consumer', 'Admin\Admin@activateConsumer');
    Route::post('/delete-consumer', 'Admin\Admin@deleteConsumer');
    Route::get('/get-consumers-small-profile', 'Admin\Admin@getConsumersSmallProfile');
    Route::post('/suspend-consumer', 'Admin\Admin@suspendConsumer');

});