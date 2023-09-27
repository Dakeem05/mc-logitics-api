<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        $naira = [];
        $usdt = [];
        foreach ($referees as $user) {
            $naira[] = $user->naira_balance;
            $usdt[] = $user->usdt_balance;
        }

        return response()->json([
            'size' => $refereesCount,
            'naira' => array_sum($naira),
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
