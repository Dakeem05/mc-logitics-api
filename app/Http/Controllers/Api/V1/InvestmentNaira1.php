<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Http\Controllers\Api\V1\InvestmentNaira1 as V1InvestmentNaira1;
use App\Models\InvestmentNaira1 as ModelsInvestmentNaira1;
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
          
        $recordCount = ModelsInvestmentNaira1::where('user_id', $userId)->count();
        if ($recordCount >= 3) {
            return ApiResponse::errorResponse([
                'message' => 'Maximum of three investments'
             ]);
        }
        $start_date = date('Y-m-d H:i:s', strtotime(Carbon::now()));
        $end_period = date('Y-m-d H:i:s', strtotime("36 day", strtotime(Carbon::now())));
        // // return $start_date;
        // return  $end_period;
        $user = User::where('id', $userId)->first();
         if ( $user->naira_balance >= $request->amount){
             $user->update([
                 'naira_balance' =>$user->naira_balance - $request->amount
             ]);
             $invest = ModelsInvestmentNaira1::create([
                 'amount' => $request->amount,
                 'user_id' => Auth::id(),
                 'start_date' => $start_date,
                 'end_date' => $end_period,
             ]);

         } else {
            return ApiResponse::errorResponse('Your balance is not enough, please recharge');
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
