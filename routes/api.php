<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepositController;
use App\Http\Controllers\Api\V1\InvestmentNaira1;
use App\Http\Controllers\Api\V1\InvestmentUsd1;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\TeamController;
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
        Route::get('/getUser', [AuthController::class, 'getUser']);
        Route::get('/team', [TeamController::class, 'index']);
        Route::get('/teamList', [TeamController::class, 'list']);
        Route::get('/getInvestments', [AuthController::class, 'getInvestments']);
        Route::get('/getTransactions', [AuthController::class, 'getTransactions']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/bindEmail', [AuthController::class, 'bindEmail']);
        Route::post('/changePassword', [AuthController::class, 'changePassword']);
        Route::post('/changeAssetPassword', [AuthController::class, 'changeAssetPassword']);
        Route::post('/me', [AuthController::class, 'me']);
    
    });

    Route::group(['middleware' => 'auth'], function ($router) {
        Route::post('/deposit', [DepositController::class, 'initializePayment']);
        Route::post('/createUsdtPayment', [DepositController::class, 'createUsdtPayment']);
        Route::get('/getUsdtPayment/{id}', [DepositController::class, 'getUsdtPayment']);
        Route::get('/fetchBank', [WithdrawController::class, 'fetchBanks']);
        Route::post('/createRecipient', [WithdrawController::class, 'createRecipient']);
        Route::post('/initiateTransfer', [WithdrawController::class, 'initiateTransfer']);
        Route::post('/initiateUsdt', [WithdrawController::class, 'initiateUsdt']);
        Route::post('/invest', [InvestmentNaira1::class, 'store']);
        Route::post('/investUsdt', [InvestmentUsd1::class, 'store']);
        Route::get('/confirmPaystack/{id}', [DepositController::class, 'handleCallback']);
        Route::get('/transactions', [InvoiceController::class, 'show3']);
        Route::get('/admin', [AdminController::class, 'index']);
        Route::get('/admin/transaction', [AdminController::class, 'transaction']);
        Route::get('/admin/users', [AdminController::class, 'users']);
    });

});