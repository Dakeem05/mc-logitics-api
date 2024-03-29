<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Models\UsdtWithdrawal;
use App\Models\User;
use App\Models\Withdraw;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;

class WithdrawController extends Controller
{
    public function fetchBanks () 
    {
        $client = new Client();
        // return env('PAYSTACK_PAYMENT_URL').'/paymentrequest/'.$id;
        $record = $client->request('GET', env('PAYSTACK_PAYMENT_URL').'/bank',
        ['headers' => ['Authorization' => 'Bearer '.env('PAYSTACK_SECRET_KEY')]]);
    
        $banks = json_decode($record->getBody())->data;

        return response()->json([
            'banks' => $banks
        ]);
        // return ApiResponse::successResponse([$banks]);
    }

    public function createRecipient (Request $request) 
    {
        $rules = [
            'name' => ['required'],
            // 'type' =>'nuban',
            'account_number' => ['required', 'numeric', 'digits:10'],
            'bank_name' => ['required'],
        ];
        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }

        $user = User::where('id', Auth::id())->first();
        $user->update([
            // 'transfer_recipient' => $recipientCode,
            'user_bank_name' => $request->name,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
        ]);

        return ApiResponse::successResponse('Card created');
        // $data = array(
        //     "type"=> 'nuban',
        //     "name"=> $request->name,
        //     "account_number"=> $request->account_number,
        //     "bank_code"=> $request->bank_code,
        //     "currency"=> "NGN"
        // );
        

        
        // $client = new Client();

        // // return env('PAYSTACK_PAYMENT_URL').'/paymentrequest/'.$id;

        // try {
        //     // Make the POST request to the API
        //     $response = $client->post(env('PAYSTACK_PAYMENT_URL') . '/transferrecipient', [
        //         'json' => $data,
        //         'headers' => [
        //             'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
        //             'Content-Type' => 'application/json', // Set the content type
        //         ],
        //     ]);
    
        //     // Get the response body as JSON
        //     $responseData = json_decode($response->getBody())->data;
        //     // return $responseData->details->account_number;
        //     $recipientCode = $responseData->recipient_code;
        //     $id = Auth::id();

        //     $user = User::where('id', $id)->first();
        //     $user->update([
        //         'transfer_recipient' => $recipientCode,
        //         'user_bank_name' => $responseData->details->account_name,
        //         'bank_name' => $responseData->details->bank_name,
        //         'account_number' => $responseData->details->account_number,
        //     ]);
        //     return ApiResponse::successResponse('Card created');
        // } catch (RequestException $e) {
        //     // Handle exceptions or errors here
        //     return ApiResponse::errorResponse([
        //         'message' => 'Failed to create recipient: ' . $e->getMessage(),
        //     ], $e->getCode());
        // }
    }

    public function initiateTransfer (Request $request) 
    {
        $rules = [
            'amount' => 'required|numeric|min:6000',
            'asset_password' => 'required'
        ];
        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }

        $id = Auth::id();

        $user = User::where('id', $id)->first();
        if(!Hash::check($request->asset_password, $user->asset_password)){
            return ApiResponse::errorResponse('Incorrect asset password');
        }
        
        if($user->usdt_balance < $request->amount/1000){
            return ApiResponse::errorResponse('Insufficient balance');
        } else {
           
        // $data = array(
        //     "source"=> 'balance',
        //     "reason"=> 'Withdrawal',
        //     "account_number"=> $request->amount * 100,
        //     "recipient"=> $user->transfer_recipient,
        //     "currency"=> "NGN"
        // );
        $user = User::where('id', Auth::id())->first();
        $user->update([
            'usdt_balance' => $user->usdt_balance - $request->amount/1000
        ]);
        $withdraw = Withdraw::create([
            'user_id' => Auth::id(),
            'amount' => $request->amount,
            'account_number' => Auth::user()->account_number,
            'bank_name' => Auth::user()->bank_name,
            'user_bank_name' => Auth::user()->user_bank_name,
            'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
        ]);
        return ApiResponse::successResponse('Withdrawal has been queued for processing');

        
        // $client = new Client();

        // return env('PAYSTACK_PAYMENT_URL').'/paymentrequest/'.$id;

        // try {
        //     // Make the POST request to the API
        //     $response = $client->post(env('PAYSTACK_PAYMENT_URL') . '/transfer', [
        //         'json' => $data,
        //         'headers' => [
        //             'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
        //             'Content-Type' => 'application/json', // Set the content type
        //         ],
        //     ]);
    
        //     // Get the response body as JSON
        //     $responseData = json_decode($response->getBody())->data;
        //     $transferCode = $responseData->transfer_code;
            
        //     $user->update([
        //         'transfer_code' => $transferCode
        //     ]);
        //     return ApiResponse::successResponse('Card created');
        // } catch (RequestException $e) {
        //     // Handle exceptions or errors here
        //     return ApiResponse::errorResponse([
        //         'message' => 'Failed to create recipient: ' . $e->getMessage(),
        //     ], $e->getCode());
        // }
         
    }
    }


    public function initiateUsdt (Request $request)
    // {
    //     $rules = [
    //         'wallet' => 'required',
    //         'amount' => 'required|numeric|min:5',
    //         'asset_password' => 'required'
    //     ];

    //     $validation = Validator::make($request->all(), $rules);
    //     if ( $validation->fails() ) {
    //         return ApiResponse::validationError([
    //                 "message" => $validation->errors()->first()
    //             ]);
    //     }

    //     $id = Auth::id();

    //     $user = User::where('id', $id)->first();
    //     if(!Hash::check($request->asset_password, $user->asset_password)){
    //         return ApiResponse::errorResponse('Incorrect asset password');
    //     }

    //     if($user->usdt_balance < $request->amount){
    //         return ApiResponse::errorResponse('Insufficient balance');
    //     } else {
            
    //         if($user->has_invested == false) {
    //             return ApiResponse::errorResponse('You have to invest to be eligible to withdraw');
    //         }
        

    //     $response = Http::withHeaders([
    //         'x-api-key' => env('NOWPAYMENTS_TEST_API_KEY'),
    //         // 'x-api-key' => env('NOWPAYMENTS_API_KEY'),
    //         'Content-Type' => 'application/json',
    //     ])
    //     ->post('https://api-sandbox.nowpayments.io/v1/payout/validate-address', [
    //     // ->post('https://api.nowpayments.io/v1/payment', [
    //         "address" => $request->wallet,
    //         "currency" => "usdttrc20",
    //         "extra_id" => null
    //     ]);
        
    //     $res = json_decode($response->getBody());
        
        
    //     if($res !== null){
    //         return ApiResponse::errorResponse($res);
    //     }
    //     $tax = $request->amount * 0.1;
    //     $amount = $request->amount - $tax;

    //     $response = Http::post('https://payid19.com/api/v1/create_withdraw', [
    //         // ->post('https://api.nowpayments.io/v1/payment', [
    //             'public_key' => env('PAYID_PUBLIC_KEY'),
    //             'private_key' => env('PAYID_SECRET_KEY'),
    //             'coin' => 'USDT',
    //             'network' => 'TRC20',
    //             'address' => $request->wallet,
    //             'amount' => $amount,
    //             //'margin_ratio' => 1
    //         ]);
        
    //     $res = json_decode($response->getBody());
    //     //  $withdraw = UsdtWithdrawal::create([
    //     //     'user_id'=> Auth::id(),
    //     //     'amount' => $amount,
    //     //     'date' =>  date("Y-m-d"),
    //     //     'address' => $request->wallet
    //     // ]);

    //     return $response;
    //     // return ApiResponse::successResponse('Withdrawal has been queued for processing');
    // }
    // }
    {
        $rules = [
            'wallet' => 'required',
            'amount' => 'required|numeric|min:10',
            'asset_password' => 'required'
        ];

        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }

        $id = Auth::id();

        $user = User::where('id', $id)->first();
        if(!Hash::check($request->asset_password, $user->asset_password)){
            return ApiResponse::errorResponse('Incorrect asset password');
        }

        if($user->usdt_balance < $request->amount){
            return ApiResponse::errorResponse('Insufficient balance');
        } else {
            
            if($user->has_invested == false) {
                return ApiResponse::errorResponse('You have to invest to be eligible to withdraw');
            }
        //    $body = '{
        //     "address": "0g033BbF609Ed876576735a02fa181842319Dd8b8F",
        //     "currency": "eth",
        //     "extra_id": null
        //   }';
        //   $request = new Request('POST', 'https://api-sandbox.nowpayments.io/v1/payout/validate-address', $headers, $body);
        //   $res = $client->sendAsync($request)->wait();
        //   echo $res->getBody();
        

        $response = Http::withHeaders([
            'x-api-key' => env('NOWPAYMENTS_TEST_API_KEY'),
            // 'x-api-key' => env('NOWPAYMENTS_API_KEY'),
            'Content-Type' => 'application/json',
        ])
        ->post('https://api-sandbox.nowpayments.io/v1/payout/validate-address', [
        // ->post('https://api.nowpayments.io/v1/payment', [
            "address" => $request->wallet,
            "currency" => "usdttrc20",
            "extra_id" => null
        ]);
        
        $res = json_decode($response->getBody());
        
        if($res !== null){
            return ApiResponse::errorResponse($res);
        }
        $tax = $request->amount * 0.1;
        $amount = $request->amount - $tax;

         $withdraw = UsdtWithdrawal::create([
            'user_id'=> Auth::id(),
            'amount' => $amount,
            'date' =>  date("Y-m-d"),
            'address' => $request->wallet
        ]);
        
         $user = User::where('id', Auth::id())->first();
        $user->update([
            'usdt_balance' => $user->usdt_balance - $request->amount
        ]);

        return ApiResponse::successResponse('Withdrawal has been queued for processing');

        //Will occur from admin side

        // $tokenResult = Http::withHeaders([
        //     'Authorization' => 'Bearer '.env('NOWPAYMENTS_TEST_API_KEY'),
        //     'x-api-key' => env('NOWPAYMENTS_TEST_API_KEY'),
        //     // 'x-api-key' => env('NOWPAYMENTS_API_KEY'),
        //     'Content-Type' => 'application/json',
        //     ])
        //     ->post('https://api-sandbox.nowpayments.io/v1/auth', [
        //         // ->post('https://api.nowpayments.io/v1/payment', [
        //             "email" => "edidiongsamuel14@gmail.com",
        //             "password" => "Iamawebdev@01" 
                  
        //         ]);

        // $respToken = json_decode($tokenResult->getBody());

        // // return $respToken->token;
        // $result = Http::withHeaders([
        //     'Authorization' => 'Bearer ' .$respToken->token,
        //     'x-api-key' => env('NOWPAYMENTS_TEST_API_KEY'),
        //     // 'x-api-key' => env('NOWPAYMENTS_API_KEY'),
        //     'Content-Type' => 'application/json',
        //     ])
        //     ->post('https://api-sandbox.nowpayments.io/v1/payout', [
        //         // ->post('https://api.nowpayments.io/v1/payment', [
        //             "ipn_callback_url" => "https://nowpayments.io",
        //             "withdrawals" => [
        //                     "address" => $request->wallet,
        //                     "currency" => "usdttrc20",
        //                     'amount' => $amount,
        //                     "ipn_callback_url" => "https://nowpayments.io"
        //                 ],
                  
        //         ]);

        // $resp = json_decode($result->getBody());



        // $withdraw = UsdtWithdrawal::create([
        //     'user_id'=> Auth::id(),
        //     'amount' => $resp->withdrawals->amount,
        //     'address' => $resp->withdrawals->address,
        //     'status' => $resp->withdrawals->status,
        //     'payout_id' => $resp->id,
        //     'batch_id' => $resp->withdrawals->batch_withdrawal_id,
        // ]);
                // return $resp;


                // return $withdraw;
        
        // $client = new Client();


        // try {
        //     // Make the POST request to the API
        //     $response = $client->post(env('PAYSTACK_PAYMENT_URL') . '/transfer', [
        //         'json' => $data,
        //         'headers' => [
        //             'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
        //             'Content-Type' => 'application/json', // Set the content type
        //         ],
        //     ]);
    
        //     // Get the response body as JSON
        //     $responseData = json_decode($response->getBody())->data;
        //     $transferCode = $responseData->transfer_code;
            
        //     $user->update([
        //         'transfer_code' => $transferCode
        //     ]);
        //     return ApiResponse::successResponse('Card created');
        // } catch (RequestException $e) {
        //     // Handle exceptions or errors here
        //     return ApiResponse::errorResponse([
        //         'message' => 'Failed to create recipient: ' . $e->getMessage(),
        //     ], $e->getCode());
        // }
         
    }
    }

    public function getUsdtWithdrawal () 
    {
        // Get all usdt withdrawals
        $id = Auth::id();
        $withdrawals = UsdtWithdrawal::where('user_id', $id)->latest()->paginate(10);

        return ApiResponse::successResponse($withdrawals);
    }
}
