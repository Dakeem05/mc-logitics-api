<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Models\Deposit;
use App\Models\DepositUsdt;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Unicodeveloper\Paystack\Paystack;

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
        //
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
        $data = array(
            "amount" => $request->amount * 100,
            "reference" => $random,
            "email" => $request->email,
            "currency" => "NGN",
            "id" => $randomNumber,
            'callback_url' => 'http://localhost:5173/callback'
        );
        
        $paystack = new Paystack();
        // $paymentLink = $paystack->getAuthorizationUrl($data)->redirectNow();

           // Get the authorization URL
           $paymentLink = $paystack->getAuthorizationUrl($data);
            Deposit::create([
                'user_id' => Auth::id(),
                'email' => $request->email,
                'is_usdt' => false,
                "reference" => $random,
                'amount' => $request->amount,
            ]);
        return response()->json(['paymentLink' => $paymentLink]);
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
            'naira_balance' => $user->naira_balance + ($payment->amount/100)
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
        return ApiResponse::errorResponse('This deposite has already reflected in your balance');
    }
}

    public function createUsdtPayment (Request $request) 
    {
        $rules = [
            'amount' =>  'required|numeric|min:15',
        ];
        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }
        $random = Str::random(5);
        $randomNumber = random_int(10000, 99999);
        $response = Http::withHeaders([
            'x-api-key' => env('NOWPAYMENTS_TEST_API_KEY'),
            // 'x-api-key' => env('NOWPAYMENTS_API_KEY'),
            'Content-Type' => 'application/json',
        ])
        ->post('https://api-sandbox.nowpayments.io/v1/payment', [
        // ->post('https://api.nowpayments.io/v1/payment', [
            "price_amount" => $request->amount,
            // "case" => 'failed',
            "price_currency" => "usd",
            "pay_currency" => "usdterc20",
            "ipn_callback_url" => "https://nowpayments.io",
            "order_id" => $random.'-'.$randomNumber,
            "order_description" => "Deposit"
        ]);

        $res = json_decode($response->getBody());
        $user = Auth::user();
        $payment = DepositUsdt::create([
            'user_id' => $user->id,
            'price' => $request->amount,
            'payment_id' => $res->payment_id,
            'is_usdt' => true,
            'pay_address' => $res->pay_address,
            'pay_amount' => $res->pay_amount,
        ]);
        return $payment;        
        // echo $payment;        
    }

    public function getUsdtPayment (string $id) 
    {
        $response = Http::withHeaders([
            'x-api-key' => env('NOWPAYMENTS_TEST_API_KEY'),
            // 'x-api-key' => env('NOWPAYMENTS_API_KEY'),
        ])
        ->get('https://api-sandbox.nowpayments.io/v1/payment/'.$id);
        
        $res = json_decode($response->getBody());

        $deposit = DepositUsdt::where('payment_id', $id)->first();
        
        if($res->payment_status == 'finished') {
            if($deposit->used == false) {
                $deposit->update([
                    'status' => 'success',
                    'used' => true,
                    'is_usdt' => true,
                    'actually_paid' => ceil($res->outcome_amount),
                    'pay_amount' => $res->pay_amount,
                ]);
                $user_id = Auth::id();
                $user = User::where('id', $user_id)->first();
                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'is_usdt' => true,
                    'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
                    'amount' => ceil($res->outcome_amount),
                    'reason' => 'Deposit',
                    'isPositive' => true,
                ]);
                $user->update([
                    'usdt_balance' => $user->usdt_balance + ceil($res->outcome_amount)
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
