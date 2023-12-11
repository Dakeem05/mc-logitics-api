<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Models\DepositUsdt;
use App\Models\Invoice;
use App\Models\UsdtWithdrawal;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    public function index ()
    {
        $user = User::latest()->take(10)->get();
        $invoice = DepositUsdt::where('status', 'success')->latest()->take(10)->get();
        $count = User::count();
        $active = User::where('has_invested', true)->count();
        $inactive = User::where('has_invested', false)->count();

        $usdt = [];
        $naira = [];
        foreach ($user as $in) {
            $naira[] = $in->naira_balance;
            $usdt[] = $in->usdt_balance;
        }

        return response()->json([
            'users' => $user,
            'invoice' => $invoice,
            'active' => $active,
            'inactive' => $inactive,
            'userCount' => $count,
            'naira' => array_sum($naira),
            'usdt' => array_sum($usdt),
        ]);
    }

    public function transaction ()
    {
        $invoice = Invoice::latest()->paginate(20);

        return $invoice;
    }

    public function makeAdmin (string $id)
    {
        $user = User::where('id', $id)->first();

        $user->update([
            'role' => 'admin'
        ]);
        // return $invoice;
    }

    public function unmakeAdmin (string $id)
    {
        $user = User::where('id', $id)->first();

        $user->update([
            'role' => 'client'
        ]);
        // return $invoice;
    }

    public function users ()
    {
        $user = User::latest()->take(3)->get();

        return $user;
    }
    public function allUsers ()
    {
        $user = User::latest()->paginate(20);

        return $user;
    }

    public function getUsdtWithdrawal () 
    {
        $withdrawals = UsdtWithdrawal::latest()->paginate(10);

        return ApiResponse::successResponse($withdrawals);
    }

    public function getWithdrawals () 
    {
        $withdrawals = Withdraw::latest()->paginate(10);
        return ApiResponse::successResponse($withdrawals);
    }

    public function completeWithdrawal (string $id) 
    {
        $withdrawal = UsdtWithdrawal::where('id', $id)->first();

        $user = User::where('id', $withdrawal->user_id)->first();
        // $user->update([
        //     'usdt_balance' => $user->usdt_balance - $withdrawal->amount
        // ]);
        $withdrawal->update([
            'is_verified' => true,
        ]);
    }

    public function completeNairaWithdrawal (string $id) 
    {
        $withdrawal = Withdraw::where('id', $id)->first();

        // $user = User::where('id', $withdrawal->user_id)->first();
        // $user->update([
        //     'usdt_balance' => $user->usdt_balance - $withdrawal->amount
        // ]);
        $withdrawal->update([
            'is_sent' => true,
        ]);
    }

    public function acceptAllWithdrawal () 
    {
        $withdrawals = UsdtWithdrawal::oldest()->get();

        // return $withdrawals;

          //Will occur from admin side

        $tokenResult = Http::withHeaders([
            'Authorization' => 'Bearer '.env('NOWPAYMENTS_TEST_API_KEY'),
            'x-api-key' => env('NOWPAYMENTS_TEST_API_KEY'),
            // 'x-api-key' => env('NOWPAYMENTS_API_KEY'),
            'Content-Type' => 'application/json',
            ])
            ->post('https://api-sandbox.nowpayments.io/v1/auth', [
                // ->post('https://api.nowpayments.io/v1/payment', [
                    "email" => "edidiongsamuel14@gmail.com",
                    "password" => "Iamawebdev@01" 
                  
                ]);

        $respToken = json_decode($tokenResult->getBody());

        // return $respToken->token;
        $resp ='';
        foreach ($withdrawals as $withdrawal) {
            # code...
            $result = Http::withHeaders([
                'Authorization' => 'Bearer ' .$respToken->token,
                'x-api-key' => env('NOWPAYMENTS_TEST_API_KEY'),
                // 'x-api-key' => env('NOWPAYMENTS_API_KEY'),
                'Content-Type' => 'application/json',
                ])
                ->post('https://api-sandbox.nowpayments.io/v1/payout', [
                    // ->post('https://api.nowpayments.io/v1/payment', [
                        "ipn_callback_url" => "https://nowpayments.io",
                        "withdrawals" => [
                                "address" => $withdrawal->wallet,
                                "currency" => "usdttrc20",
                                'amount' => $withdrawal->amount,
                                "ipn_callback_url" => "https://nowpayments.io"
                            ],
                      
                    ]);
    
            $resp = json_decode($result->getBody());
        }

        return ApiResponse::successResponse($resp);
    }

    public function getBalance ()
    {

            $response = Http::post('https://payid19.com/api/v1/get_balance', [
                // ->post('https://api.nowpayments.io/v1/payment', [
                    'public_key' => env('PAYID_PUBLIC_KEY'),
                    'private_key' => env('PAYID_SECRET_KEY')
                    //'margin_ratio' => 1
                ]);
            return $response;
            $res = json_decode($response->getBody());
    }
}
