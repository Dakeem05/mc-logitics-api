<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Models\Deposit;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'email' => ['required'],
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
                "reference" => $random,
                'amount' => $request->amount,
            ]);
        return response()->json(['paymentLink' => $paymentLink]);
    }

    public function handleCallback( string $id)
{
    // $paymentReference = $request->input('ref'); // Get the payment reference from the callback

    // Verify the payment with Paystack API
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
        $user = User::where('id', $deposit->user_id)->first();
        $user->update([
            'naira_balance' => $user->naira_balance + ($payment->amount/100)
        ]);
        $deposit->update([
            'used' => true
        ]);
        return $user;
    } else {
        return ApiResponse::errorResponse('This deposite has already reflected in your balance');
    }
    // if (!$payment) {
    //     return response()->json(['message' => 'Payment not found'], 404);
    // }

    // // Update the payment status in your database
    // $payment->update([
    //     'status' => 'completed', // You can use a different status value as needed
    //     // Add other fields you want to update here
    // ]);
    // $paymentDetails = $paystack->genTranxRef($paymentReference);
    // return ;
    // if (!$paymentDetails['status']) {
    //     // Payment verification failed
    //     return response()->json(['message' => 'Payment verification failed'], 400);
    // }

    // // Update the payment status in your database
    // $payment = Deposit::where('reference', $paymentReference)->first();

    // if (!$payment) {
    //     return response()->json(['message' => 'Payment not found'], 404);
    // }

    // // Update the payment status in your database
    // $payment->update([
    //     'status' => 'completed', // You can use a different status value as needed
    //     // Add other fields you want to update here
    // ]);

    // // Perform any other actions you need here, such as sending email notifications, etc.

    // return response()->json(['message' => 'Payment verified and request updated'], 200);
}

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
