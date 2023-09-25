<?php

namespace App\Console\Commands\Api\V1;

use App\Models\InvestmentUsd1;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteExpiredUsdtInvestments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-expired-usdt-investments';

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
        // Get the current date
      $currentDate = date('Y-m-d H:i:s', strtotime(Carbon::now()));

      // Find investments whose end date has passed

      $expiredInvestments = InvestmentUsd1::where('end_date', '<=',$currentDate)->get();
      // return ; 
      // $this->info($currentDate. ' expired investments have been deleted.');

          // Delete the expired investments
          foreach ($expiredInvestments as $investment) {
  
              $investment->delete();
          }
  
          $this->info(count($expiredInvestments) . ' expired investments have been deleted.');

  }
}
