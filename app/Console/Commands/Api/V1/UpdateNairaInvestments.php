<?php

namespace App\Console\Commands\Api\V1;

use App\Models\InvestmentNaira1;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateNairaInvestments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-naira-investments';

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
        $investments = InvestmentNaira1::get();
        $invest = [];
        foreach ($investments as $investment) {
            $user = User::where('id', $investment->user_id)->first();
            $amount = $investment->amount;
            $interest = $amount * 0.05;
            $investment->update([
                'cummulative_interest' => $investment->cummulative_interest + $interest
            ]);
            $user->update([
                'naira_balance' => $user->naira_balance + $interest
            ]);
            $invest[]  = $interest;
        }
        $this->info(count($investments) . ' investments investments have been updated');

    }
}