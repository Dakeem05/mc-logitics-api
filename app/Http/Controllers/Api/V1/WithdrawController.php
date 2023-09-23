<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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
            'bank_code' => ['required'],
        ];
        $validation = Validator::make($request->all(), $rules);
        if ( $validation->fails() ) {
            return ApiResponse::validationError([
                    "message" => $validation->errors()->first()
                ]);
        }
        $data = array(
            "type"=> 'nuban',
            "name"=> $request->name,
            "account_number"=> $request->account_number,
            "bank_code"=> $request->bank_code,
            "currency"=> "NGN"
        );
        

        
        $client = new Client();

        // return env('PAYSTACK_PAYMENT_URL').'/paymentrequest/'.$id;

        try {
            // Make the POST request to the API
            $response = $client->post(env('PAYSTACK_PAYMENT_URL') . '/transferrecipient', [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                    'Content-Type' => 'application/json', // Set the content type
                ],
            ]);
    
            // Get the response body as JSON
            $responseData = json_decode($response->getBody())->data;
            // return $responseData->details->account_number;
            $recipientCode = $responseData->recipient_code;
            $id = Auth::id();

            $user = User::where('id', $id)->first();
            $user->update([
                'transfer_recipient' => $recipientCode,
                'user_bank_name' => $responseData->details->account_name,
                'bank_name' => $responseData->details->bank_name,
                'account_number' => $responseData->details->account_number,
            ]);
            return ApiResponse::successResponse('Card created');
        } catch (RequestException $e) {
            // Handle exceptions or errors here
            return ApiResponse::errorResponse([
                'message' => 'Failed to create recipient: ' . $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function initiateTransfer (Request $request) 
    {
        $rules = [
            'amount' => 'required|numeric|min:1500',
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
        
        if($user->naira_balance < $request->amount){
            return ApiResponse::errorResponse('Insufficient balance');
        } else {
           
        $data = array(
            "source"=> 'balance',
            "reason"=> 'Withdrawal',
            "account_number"=> $request->amount * 100,
            "recipient"=> $user->transfer_recipient,
            "currency"=> "NGN"
        );
        

        
        $client = new Client();

        // return env('PAYSTACK_PAYMENT_URL').'/paymentrequest/'.$id;

        try {
            // Make the POST request to the API
            $response = $client->post(env('PAYSTACK_PAYMENT_URL') . '/transfer', [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY'),
                    'Content-Type' => 'application/json', // Set the content type
                ],
            ]);
    
            // Get the response body as JSON
            $responseData = json_decode($response->getBody())->data;
            $transferCode = $responseData->transfer_code;
            
            $user->update([
                'transfer_code' => $transferCode
            ]);
            return ApiResponse::successResponse('Card created');
        } catch (RequestException $e) {
            // Handle exceptions or errors here
            return ApiResponse::errorResponse([
                'message' => 'Failed to create recipient: ' . $e->getMessage(),
            ], $e->getCode());
        }
         
    }
    }
}
