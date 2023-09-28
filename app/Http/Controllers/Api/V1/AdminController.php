<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Models\Invoice;
use App\Models\UsdtWithdrawal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    public function index ()
    {
        $user = User::latest()->take(10)->get();
        $invoice = Invoice::latest()->take(10)->get();
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
        $invoice = Invoice::latest()->take(10)->get();

        return $invoice;
    }

    public function users ()
    {
        $user = User::latest()->take(10)->get();

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

    public function acceptWithdrawal (string $id) 
    {
        $withdrawals = UsdtWithdrawal::where('id', $id)->first();

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
                            "address" => $withdrawals->wallet,
                            "currency" => "usdttrc20",
                            'amount' => $withdrawals->amount,
                            "ipn_callback_url" => "https://nowpayments.io"
                        ],
                  
                ]);

        $resp = json_decode($result->getBody());

        return ApiResponse::successResponse($resp);
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
}
