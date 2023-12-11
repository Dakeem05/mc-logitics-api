<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\InvestmentUsd1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeamController extends Controller
{
    public function index ()
    {
        $id = Auth::user();

        $ref = $id->ref_code;

        $refereesCount = User::where('referer_code', $ref)->count();

        $referees = User::where('referer_code', $ref)->get();

        $invest = [];
        $investments = [];
        $usdt = [];
        foreach ($referees as $user) {
            // $naira[] = $user->naira_balance;
            $usdt[] = $user->usdt_balance;
            // $invest[] = $user->team_earning;
        // $investments = InvestmentUsd1::where('user_id', $user->id)->get();
        }
        // if($investments){
        // foreach ($investments as $user) {
        //     $invest[] = $user->amount;
        // // $investments[] = InvestmentUsd1::where('user_id', $user->id)->get();
        // }
        // }
        
        // $
        // $user = User::where('id', $id)->first();

        return response()->json([
            'size' => $refereesCount,
            'invest' => $id->team_earning,
            // 'invest' => array_sum($invest) ,
            'usdt' => array_sum($usdt),
        ]);
    }


    public function list ()
    {
        $id = Auth::user();

        $ref = $id->ref_code;

        // $refereesCount = User::where('referer_code', $ref)->count();

        $referees = User::where('referer_code', $ref)->get();

        // $email = [];
        // $phone = [];
        // $userCount = [];
        // $isActive = [];

        $person = [];
        foreach ($referees as $user) {
            $code = $user->ref_code;

            $userCount =  User::where('referer_code', $code)->count();
            $person[] = [
                'email' => $user->email,
                'phone' => $user->phone,
                'active' => $user->has_invested,
                'size' => $user->userCount,
            ];
            // $email[] = $user->email;
            // $phone[] = $user->phone;
            // $isActive[] = $user->has_invested;

        }

        // $result = [
        //     'email' => $email,
        //     'phone' => $phone,
        //     'size' => $userCount,
        // ];

        return $person;
    }
}
