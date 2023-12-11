<?php

namespace App\Console\Commands\Api\V1;

use App\Models\InvestmentUsd1;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateUsdtInvestments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-usdt-investments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
     
     public function handle()
    {
        $currentTime = Carbon::now();
        // $currentTime2 = Carbon::now();
        
        $fourMinutesAgo = Carbon::now()->addMinutes(4); // Subtract 5 minutes from the current time
        // dd($fourMinutesAgo->format('H:i'));
        // Get investments that are scheduled for the current time
        
        // $fourMinutesAgo = $currentTime->subMinutes(4); // Subtract 5 minutes from the current time
        $threeMinutesAgo = Carbon::now()->addMinutes(3); // Subtract 5 minutes from the current time
        $twoMinutesAgo = Carbon::now()->addMinutes(2); // Subtract 5 minutes from the current time
        $oneMinutesAgo = Carbon::now()->addMinutes(1); // Subtract 5 minutes from the current time
// $investments = InvestmentUsd1::whereTime('time',  $currentTime->format('H:i'))->get();
//      $investments = InvestmentUsd1::where(function($query) use ($currentTime, $fourMinutesAgo, $threeMinutesAgo, $twoMinutesAgo, $oneMinutesAgo) {
//     $query->where('time', $currentTime->format('H:i:s'))
//           ->orWhere('time', $fourMinutesAgo->format('H:i'))
//           ->orWhere('time', $threeMinutesAgo->format('H:i'))
//           ->orWhere('time', $twoMinutesAgo->format('H:i'))
//           ->orWhere('time', $oneMinutesAgo->format('H:i'));
// })->get();
      $investments = InvestmentUsd1::where('time', $currentTime->format('H:i'))->orWhere('time', $fourMinutesAgo->format('H:i'))->orWhere('time', $threeMinutesAgo->format('H:i'))->orWhere('time', $twoMinutesAgo->format('H:i'))->orWhere('time', $oneMinutesAgo->format('H:i'))->get();
// dd($investments);
// 

        $invest = [];

        foreach ($investments as $investment) {

             if($investment->days >= 36){
                 $investment->delete();
            }
            $user = User::find($investment->user_id);
            

            $amount = $investment->amount;
            $interest = $amount * 0.06;

            $investment->update([
                'cummulative_interest' => $investment->cummulative_interest + $interest,
                'days' => $investment->days + 1,
            ]);

            $invoice = Invoice::create([
                'user_id' => $user->id,
                'is_usdt' => true,
                'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())), // Use the current time
                'amount' => $interest,
                'reason' => 'Daily income',
                'isPositive' => true,
            ]);

            $user->update([
                'usdt_balance' => $user->usdt_balance + $interest,
            ]);

            $invest[] = $interest;
        }

        $this->info(count($investments) . ' investments have been updated');
    }
     
     
    // public function handle()
    // {
    //     $investments = InvestmentUsd1::get();
    //     $invest = [];
    //     foreach ($investments as $investment) {

    //         if($investment->days >= 36){
    //              $investment->delete();
    //         }
            
            
    //         else {
    //         $user = User::where('id', $investment->user_id)->first();
    //         $amount = $investment->amount;
    //         $interest = $amount * 0.06;
    //         // dd($investment->time);
    //         // $this->info($investment->time. ' expired investments have been deleted.');
    //         // return;
            
            
    //           $scheduledTime = $investment->time; // Replace with the actual column name

    //         // Convert the scheduled time to a Carbon object
    //         $scheduleTime = Carbon::createFromFormat('H:i:s', $scheduledTime);

    //         // Get the current time
            
    //                   $currentTime = now()->format('H:i:s');

    //     if ($scheduleTime === $currentTime) {
    //         // Perform your cron job logic here
    //                                   $investment->update([
    //             'cummulative_interest' => $investment->cummulative_interest + $interest,
    //             'days' => $investment->days + 1,
    //         ]);
    //             $invoice = Invoice::create([
    //                 'user_id' => $user->id,
    //                 'is_usdt' => true,
    //                 'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
    //                 'amount' => $interest,
    //                 'reason' => 'Daily income',
    //                 'isPositive' => true,
    //             ]);
    //             $user->update([
    //                 'usdt_balance' => $user->usdt_balance + $interest
    //             ]);
                
    //             $invest[]  = $interest;
    //         //     $this->info('Cron job is running.');
    //         $this->info('Cron job is running.');
    //     } else {
    //         $this->info('Cron job is not scheduled to run at this time.');
    //               dd(date('H:i:s', strtotime(Carbon::now())));
    //     }
            
            
    //         // $now = now();

    //         // if ($scheduleTime->isPast()) {
    //         //     // If the scheduled time has already passed today, add a day to it
    //         //     $scheduleTime->addDay();
    //         //     dd(date('H:i:s', strtotime(Carbon::now())));
    //         // }

    //         // $this->info('Scheduled time: ' . $scheduleTime->format('H:i:s'));

    //         // // Define the schedule
    //         // $this->callAfter($scheduleTime, function () {
    //         //     // Your cron job logic here
    //         //                     $investment->update([
    //         //     'cummulative_interest' => $investment->cummulative_interest + $interest,
    //         //     'days' => $investment->days + 1,
    //         // ]);
    //         //     $invoice = Invoice::create([
    //         //         'user_id' => $user->id,
    //         //         'is_usdt' => true,
    //         //         'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
    //         //         'amount' => $interest,
    //         //         'reason' => 'Daily income',
    //         //         'isPositive' => true,
    //         //     ]);
    //         //     $user->update([
    //         //         'usdt_balance' => $user->usdt_balance + $interest
    //         //     ]);
                
    //         //     $invest[]  = $interest;
    //         //     $this->info('Cron job is running.');
    //         // });
            
            
            
    //         // if($investment->time == date('H:i:s', strtotime(Carbon::now()))){
    //         //                 $investment->update([
    //         //     'cummulative_interest' => $investment->cummulative_interest + $interest,
    //         //     'days' => $investment->days + 1,
    //         // ]);
    //         //     $invoice = Invoice::create([
    //         //         'user_id' => $user->id,
    //         //         'is_usdt' => true,
    //         //         'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
    //         //         'amount' => $interest,
    //         //         'reason' => 'Daily income',
    //         //         'isPositive' => true,
    //         //     ]);
    //         //     $user->update([
    //         //         'usdt_balance' => $user->usdt_balance + $interest
    //         //     ]);
                
    //         //     $invest[]  = $interest;
                
    //         // } else {
    //         //     dd(date('H:i:s', strtotime(Carbon::now())));
    //         // }
    //         }
    //     }
    //     $this->info(count($investments) . ' investments investments have been updated');

    // }
}
