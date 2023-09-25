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
        $investments = InvestmentUsd1::get();
        $invest = [];
        foreach ($investments as $investment) {
            $user = User::where('id', $investment->user_id)->first();
            $amount = $investment->amount;
            $interest = $amount * 0.05;
            $investment->update([
                'cummulative_interest' => $investment->cummulative_interest + $interest,
                'days' => $investment->days + 1,
            ]);
            $invoice = Invoice::create([
                'user_id' => $user->id,
                'is_usdt' => true,
                'date' => date('Y-m-d H:i:s', strtotime(Carbon::now())),
                'amount' => $interest,
                'reason' => 'Daily income',
                'isPositive' => true,
            ]);
            $user->update([
                'naira_balance' => $user->usdt_balance + $interest
            ]);
            $invest[]  = $interest;
        }
        $this->info(count($investments) . ' investments investments have been updated');

    }
}
