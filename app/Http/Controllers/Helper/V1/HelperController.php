<?php

namespace App\Http\Controllers\Helper\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelperController extends Controller
{
    public static function proratedCheck($subscription, $plan) {

        $date1 = $subscription->current_period_start;
        $date2 = \Carbon\Carbon::now();
        $datediff = strtotime($date2) - strtotime($date1);
        $days_used = abs(round($datediff / (60 * 60 * 24)));
        if($days_used < 2){ return null;}
        $amount_due = floor(($plan->actual_price / $subscription->current_period_days) * $days_used * 100)/100;
        if($amount_due < 5){ return null;}
        return (Object)[
            "amount" => $amount_due,
            "days_used" => $days_used,
            "subscription" => $subscription,
            "plan" => $plan 
        ];
    }

    public static function getDateFromPeriod($start_date, $period) {

        $billingPeriod = [ "month" => 30, "quarter" => 90, "year" => 365];
        return  (Object)[
            "days" => $billingPeriod[$period],
            "date" => strtotime("+{$billingPeriod[$period]} day", strtotime($start_date))
        ];

    }

    public static function decodeDataFactory($data)
    {
        $data = json_decode($data);
        if(is_null($data)){
            return ["error" => "Invalid data object received!!"];
        }
        $parameters = array();
        foreach($data as $key => $value){
            if($value === "" || $value === "null" || $value === "undefined"){
                $parameters[$key] = null;
            }else{
                $parameters[$key] = $value;
            }
        }
        return $parameters;
    }
}
