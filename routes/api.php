<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepositController;
use App\Http\Controllers\Api\V1\InvestmentNaira1;
use App\Http\Controllers\Api\V1\WithdrawController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['middleware' => 'api', 'prefix' => '/V1'], function ($router) {

    Route::group(['prefix' => '/auth'], function ($router) {
    
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'store']);
        Route::get('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/me', [AuthController::class, 'me']);
    
    });

    Route::group(['middleware' => 'auth'], function ($router) {
        Route::post('/deposit', [DepositController::class, 'initializePayment']);
        Route::get('/fetchBank', [WithdrawController::class, 'fetchBanks']);
        Route::post('/createRecipient', [WithdrawController::class, 'createRecipient']);
        Route::post('/initiateTransfer', [WithdrawController::class, 'initiateTransfer']);
        Route::post('/invest', [InvestmentNaira1::class, 'store']);
        Route::get('/confirmPaystack/{id}', [DepositController::class, 'handleCallback']);
    });

});