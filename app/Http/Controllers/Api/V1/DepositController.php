<?php

namespace App\Http\Controllers\Api\V1;

// php artisan migrate:refresh --path=/database/migrations/2023_09_22_191937_create_withdraws_table.php
// php artisan migrate:refresh --path=/database/migrations/2023_09_18_122755_create_deposits_table.php


use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Models\Deposit;
use App\Models\DepositNaira;
use App\Models\DepositUsdt;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use UniPayment\Client\lib\Model\CreateInvoiceRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Unicodeveloper\Paystack\Paystack;

class RequestSigner
{
    public static function signRequest($client_id, $client_secret, $request_http_method, $url, $query_params, $request_body = null)
    {
        $uri = $url;
        if (!empty($query_params)) {
            $uri .= '?' . http_build_query($query_params);
        }

        $uri = rawurlencode(strtolower($uri));

        $request_body_base64 = '';
        if (!is_null($request_body)) {
            $request_body_json = json_encode($request_body);
            $md5_hash = md5($request_body_json, true);
            $request_body_base64 = base64_encode($md5_hash);
        }

        $nonce = bin2hex(random_bytes(16));
        $request_timestamp = time();

        $raw_data = "{$client_id}{$request_http_method}{$uri}{$request_timestamp}{$nonce}{$request_body_base64}";
        $signature = hash_hmac('sha256', $raw_data, $client_secret, true);

        return "{$client_id}:" . base64_encode($signature) . ":{$nonce}:{$request_timestamp}";
    }
}

class DepositController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'email' => ['sometimes'],
            'username' => ['required', 'exists:'.User::class],
            'amount' =>  'required|numeric|min:5000',
        ];
        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }
        $user = Auth::user();
        // if($request-)
        $deposit = DepositNaira::create([
            'username' => $user->username,
            'user_id' => $user->id,
            'email' => $request->email,
            'amount' => $request->amount,
            'status' => 'sent'
        ]);
        $message = 'Your payment will be verified and your balance  will be updated with $ '.$request->amount/1000;
        return ApiResponse::successResponse($message);
    }

    public function initializePayment(Request $request)
    {
        $rules = [
            'email' => ['sometimes'],
            'amount' =>  'required|numeric|min:5000',
        ];
        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }
        $random = Str::random(20);
        $randomNumber = random_int(10000, 99999);
        $email = '';
        if ($request->email){
            $email = $request->email;
        } else {
            $email = 'useremail@gmail.com';
        }
        $data = array(
            "amount" => $request->amount * 100,
            "reference" => $random,
            "email" => $email,
            "currency" => "NGN",
            "id" => $randomNumber,
            'callback_url' => 'http://localhost:5173/callbackNaira'
            // 'callback_url' => 'https://mclogisticsintl.com/callback'
        );
        
        $paystack = new Paystack();
        // $paymentLink = $paystack->getAuthorizationUrl($data)->redirectNow();
        
        // Get the authorization URL
        // https://api.paystack.co/transaction/initialize
        // $paymentLink = $paystack->getAuthorizationUrl($data);
        $response = Http::withHeaders([
                        "Authorization"=> 'Bearer '.env('PAYSTACK_SECRET_KEY'),
                        "Cache-Control" => 'no-cache',
                    ])->post('https://api.paystack.co/transaction/initialize', $data);
                    $res = json_decode($response->getBody());
        Deposit::create([
            'user_id' => Auth::id(),
            'email' => $email,
            'is_usdt' => false,
            'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
            "reference" => $random,
            'amount' => $request->amount,
        ]);
        // return response()->json(['paymentLink' => $paymentLink]);
        return response()->json([
            // 'res' => $res->data->reference,
            'paymentLink' => $res->data->authorization_url
    ]);
    }

    public function handleCallback( string $id)
{
    $paystack = new Paystack();
    $client = new Client();
    // return env('PAYSTACK_PAYMENT_URL').'/paymentrequest/'.$id;
    $record = $client->request('GET', env('PAYSTACK_PAYMENT_URL').'/transaction/verify/'.$id,
    ['headers' => ['Authorization' => 'Bearer '.env('PAYSTACK_SECRET_KEY')]]);

    $payment = json_decode($record->getBody())->data;
    // return ;
    $deposit = Deposit::where('reference', $payment->reference)->first();

    if($deposit->used == false) {
        $renew = $deposit->update([
            'status' => $payment->status
        ]);
        $userId = Auth::id();
        $user = User::where('id', $userId)->first();
        $user->update([
            'usdt_balance' => $user->usdt_balance + ($payment->amount/100)/1000
        ]);
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'is_usdt' => false,
            'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
            'amount' => $payment->amount/100,
            'reason' => 'Deposit',
            'isPositive' => true,
        ]);
        $deposit->update([
            'used' => true
        ]);
        return $user;
    } else {
        return ApiResponse::errorResponse('This deposit has already reflected in your balance');
    }
}


    public function createUsdtPayment (Request $request) 

    {
        $rules = [
            'amount' =>  'required|numeric|min:5',
        ];
        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }
        $random = Str::random(5);
        $randomNumber = random_int(10000, 99999);
        $response = Http::post('https://payid19.com/api/v1/create_invoice', [
        // ->post('https://api.nowpayments.io/v1/payment', [
            'public_key' => env('PAYID_PUBLIC_KEY'),
            'private_key' => env('PAYID_SECRET_KEY'),
            'email' => 'email@email.com',
            'price_amount' => $request->amount,
            'price_currency' => 'USD',
            'merchant_id' => 5,
            'order_id' => $randomNumber,
            'customer_id' => 12,
            'test' => 0,
            'title' => 'Mc-Logistics',
            'description' =>"Recharge (Please do not refresh this page, wait until you are redirected)",
            'add_fee_to_price' => 0,
            'cancel_url' => 'https://mclogisticsintl.com/recharge',
            'success_url' => 'https://mclogisticsintl.com/callback',
            'callback_url' => 'https://mclogisticsintl.com/callback',
            'expiration_date' => 6,
            //'margin_ratio' => 1
        ]);
        $user = Auth::user();
        $res = json_decode($response->getBody());
        $payment = DepositUsdt::create([
                    'user_id' => $user->id,
                    'price' => $request->amount,
                    'payment_id' => $randomNumber,
                    'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
                    'is_usdt' => true,
                    'pay_amount' => $request->amount,
                ]);
                // return $payment; 

        return response()->json([
            'link' => $res->message,
            'pay_id' => $randomNumber
        ]);

//         'public_key' => 'yourpublickey',
// 'private_key' => 'yourprivatekey',
// 'email' => 'email@email.com',
// 'price_amount' => 725,
// 'price_currency' => 'USD',
// 'merchant_id' => 5,
// 'order_id' => 11,
// 'customer_id' => 12,
// 'test' => 1,
// 'title' => 'title',
// 'description' => 'description',
// 'add_fee_to_price' => 1,
// 'cancel_url' => 'https://yourcancelurl',
// 'success_url' => 'https://yoursuccessurl',
// 'callback_url' => 'http://yourcallbackurl',
// 'expiration_date' => 6,
// //'margin_ratio' => 1

        // $res = json_decode($response->getBody());
        // $user = Auth::user();
        // $payment = DepositUsdt::create([
        //     'user_id' => $user->id,
        //     'price' => $request->amount,
        //     'payment_id' => $res->payment_id,
        //     'is_usdt' => true,
        //     'pay_address' => $res->pay_address,
        //     'pay_amount' => $res->pay_amount,
        // ]);
        // return $payment;        
        // echo $payment;        
    }
//     // {
//     //     $rules = [
//     //         'amount' =>  'required|numeric|min:15',
//     //     ];
//     //     $validation = Validator::make($request->all(), $rules);
//     //     if ( $validation->fails() ) {
//     //         return ApiResponse::validationError([
//     //                 "message" => $validation->errors()->first()
//     //             ]);
//     //     }
//     //     $random = Str::random(5);
//     //     $randomNumber = random_int(10000, 99999);
//     //     $response = Http::withHeaders([
//     //         'x-api-key' => env('NOWPAYMENTS_TEST_API_KEY'),
//     //         // 'x-api-key' => env('NOWPAYMENTS_API_KEY'),
//     //         'Content-Type' => 'application/json',
//     //     ])
//     //     ->post('https://api-sandbox.nowpayments.io/v1/payment', [
//     //     // ->post('https://api.nowpayments.io/v1/payment', [
//     //         "price_amount" => $request->amount,
//     //         // "case" => 'failed',
//     //         "price_currency" => "usd",
//     //         "pay_currency" => "usdttrc20",
//     //         "ipn_callback_url" => "https://nowpayments.io",
//     //         "order_id" => $random.'-'.$randomNumber,
//     //         "order_description" => "Deposit"
//     //     ]);

//     //     $res = json_decode($response->getBody());
//     //     $user = Auth::user();
//     //     $payment = DepositUsdt::create([
//     //         'user_id' => $user->id,
//     //         'price' => $request->amount,
//     //         'payment_id' => $res->payment_id,
//     //         'is_usdt' => true,
//     //         'pay_address' => $res->pay_address,
//     //         'pay_amount' => $res->pay_amount,
//     //     ]);
//     //     return $payment;        
//     //     // echo $payment;        
//     // }

    public function getUsdtPayment (string $id) 
    {
        $response = Http::post('https://payid19.com/api/v1/get_invoices', [
            // ->post('https://api.nowpayments.io/v1/payment', [
                'public_key' => env('PAYID_PUBLIC_KEY'),
                'private_key' => env('PAYID_SECRET_KEY'),
                'order_id' => $id,
                //'margin_ratio' => 1
            ]);
        
        $res = json_decode($response->getBody());

        $deposit = DepositUsdt::where('payment_id', $id)->first();
        
        // return $deposit->price;

        $income = json_decode($res->message);

        $amount = '';

        foreach ($income as $key) {
            $amount = $key->amount;
        }
        // return $amount;
        if($amount !== null) {
            if($deposit->used == false) {
                $deposit->update([
                    'status' => 'success',
                    'used' => true,
                ]);
                // $user_id = Auth::id();

                // $depo = DepositUsdt::where('user_id', $user_id)->first();
                $user = User::where('id', $deposit->user_id)->first();
                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'is_usdt' => true,
                    'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
                    'amount' => $deposit->price,
                    'reason' => 'Deposit',
                    'isPositive' => true,
                ]);
                $user->update([
                    'usdt_balance' => $user->usdt_balance + $deposit->price
                ]);
                return ApiResponse::successResponse('Payment successful');
            } else {
                return ApiResponse::errorResponse('Payment already reflected');

            }
        } else {
            return ApiResponse::errorResponse('Payment not completed, wait for a few minutes before retrying');
        }
        return $res;
    }
    // {
    //     $response = Http::withHeaders([
    //         'x-api-key' => env('NOWPAYMENTS_TEST_API_KEY'),
    //         // 'x-api-key' => env('NOWPAYMENTS_API_KEY'),
    //     ])
    //     ->get('https://api-sandbox.nowpayments.io/v1/payment/'.$id);
        
    //     $res = json_decode($response->getBody());

    //     $deposit = DepositUsdt::where('payment_id', $id)->first();
        
    //     if($res->payment_status == 'finished') {
    //         if($deposit->used == false) {
    //             $deposit->update([
    //                 'status' => 'success',
    //                 'used' => true,
    //                 'is_usdt' => true,
    //                 'actually_paid' => ceil($res->outcome_amount),
    //                 'pay_amount' => $res->pay_amount,
    //             ]);
    //             $user_id = Auth::id();
    //             $user = User::where('id', $user_id)->first();
    //             $invoice = Invoice::create([
    //                 'user_id' => $user->id,
    //                 'is_usdt' => true,
    //                 'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
    //                 'amount' => ceil($res->outcome_amount),
    //                 'reason' => 'Deposit',
    //                 'isPositive' => true,
    //             ]);
    //             $user->update([
    //                 'usdt_balance' => $user->usdt_balance + ceil($res->outcome_amount)
    //             ]);
    //             return ApiResponse::successResponse('Payment successful');
    //         } else {
    //             return ApiResponse::errorResponse('Payment already reflected');

    //         }
    //     } else {
    //         return ApiResponse::errorResponse('Payment not completed, wait for a few minutes before retrying');
    //     }
    //     return $res;
    // }
    // {
        
    //     $client = new Client();

    //       $body = array(
    //         "price_amount" => 3999.5,
    //         "price_currency" => "usd",
    //         "pay_currency" => "btc",
    //         "ipn_callback_url" => "https://nowpayments.io",
    //         "order_id" => "RGDBP-21314",
    //         "order_description" => "Apple Macbook Pro 2019 x 1"
    //     );
    //     $headers = [
    //         'x-api-key' => '66XD9YB-4Z0MN4X-K7SDEPX-GDD9RKH',
    //         'Content-Type' => 'application/json'
    //       ];
    //     try {
    //         // Make the POST request to the API
    //         $response = $client->post('https://api.nowpayments.io/v1/payment',$headers,[
    //             'json' => $body
    //         ]);
    //         return $response->getBody();
    
    //         // Get the response body as JSON
    //         // $responseData = json_decode($response->getBody())->data;
    //         // $transferCode = $responseData->transfer_code;
            
    //         // $user->update([
    //         //     'transfer_code' => $transferCode
    //         // ]);
    //         // return ApiResponse::successResponse('Card created');
    //     } catch (RequestException $e) {
    //         // Handle exceptions or errors here
    //         return ApiResponse::errorResponse([
    //             'message' => 'Failed to create recipient: ' . $e->getMessage(),
    //         ], $e->getCode());
    //     }
    // }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
