<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Http\Controllers\Api\V1\InvestmentNaira1 as V1InvestmentNaira1;
use App\Models\InvestmentNaira1 as ModelsInvestmentNaira1;
use App\Models\InvestmentUsd1;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InvestmentNaira1 extends Controller
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
            'amount' => 'required|numeric|min:5000',
            
        ];
        $validator = Validator::make($request->all(), $rules);
        if ( $validator->fails() ) {
            return ApiResponse::validationError([
                "message" => $validator->errors()->first()
            ]);
        }
        $userId = Auth::id();

        
        $recordCount = InvestmentUsd1::where('user_id', $userId)->count();
        $recordCountNaira = ModelsInvestmentNaira1::where('user_id', $userId)->count();

        $both = $recordCount + $recordCountNaira;
        if ($both >= 3) {
            return ApiResponse::errorResponse([
                'message' => 'Maximum of three investments'
            ]);
        }
        
        $start_date = date('Y-m-d H:i:s', strtotime(Carbon::now()));
        $end_period = date('Y-m-d H:i:s', strtotime("36 day", strtotime(Carbon::now())));
        // // return $start_date;
        // return  $end_period;
        $user = User::where('id', $userId)->first();
        $referer = User::where('ref_code', $user->referer_code)->first();
        if($user->has_invested == true) {

            if ( $user->naira_balance >= $request->amount){
                $user->update([
                    'has_invested' =>true,
                    'naira_balance' =>$user->naira_balance - $request->amount,
                ]);

                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'is_usdt' => false,
                    'date' => $start_date,
                    'amount' => $request->amount,
                    'reason' => 'Investment',
                    'isPositive' => false,
                ]);
                $invest = ModelsInvestmentNaira1::create([
                    'amount' => $request->amount,
                    'is_usdt' => false,
                    'user_id' => Auth::id(),
                    'days' => 1,
                    'start_date' => $start_date,
                    'end_date' => $end_period,
                ]);
   
            } else {
               return ApiResponse::errorResponse('Your balance is not enough, please recharge.. Your balance is: '.$user->naira_balance);
            }
        } else {
            if ( $user->naira_balance >= $request->amount){
                $user->update([
                    'has_invested' =>true,
                    'naira_balance' =>$user->naira_balance - $request->amount,
                ]);
                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'is_usdt' => false,
                    'date' => $start_date,
                    'amount' => $request->amount,
                    'reason' => 'Investment',
                    'isPositive' => false,
                ]);
                $invest = ModelsInvestmentNaira1::create([
                    'amount' => $request->amount,
                    'is_usdt' => false,
                    'user_id' => Auth::id(),
                    'days' => 1,
                    'start_date' => $start_date,
                    'end_date' => $end_period,
                ]);
                if($referer){
                    $bonus = $request->amount * 0.1;
                    $referer->update([
                        'naira_balance' => $referer->naira_balance + $bonus
                    ]);
                    $invoice = Invoice::create([
                        'date' => $start_date,
                        'user_id' => $referer->id,
                    'is_usdt' => false,
                        'amount' => $request->amount * 0.1,
                        'reason' => 'Referral bonus',
                        'isPositive' => true,
                    ]);
                }
               
             
   
            } else {
               return ApiResponse::errorResponse('Your balance is not enough, please recharge.. Your balance is: '.$user->naira_balance);
            }
        }


        return $invest;
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
