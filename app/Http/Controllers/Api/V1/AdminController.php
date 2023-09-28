<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Helper\V1\ApiResponse;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;

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
        $invoice = Invoice::latest()->get();

        return $invoice;
    }

    public function users ()
    {
        $user = User::latest()->get();

        return $user;
    }
}
