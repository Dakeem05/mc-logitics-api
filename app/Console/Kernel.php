<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Models\InvestmentUsd1;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->command('app:update-naira-investments')->daily();
        
        // $id = Auth::id();
        // $user = InvestmentUsd1::where('user_id', $id)->first();
        // dd($user);
        // $time = $user->time;
        // $schedule->command('app:update-usdt-investments')->everyThirtySeconds();
        // $schedule->command('app:delete-expired-naira-investments')->daily();
        $schedule->command('app:update-usdt-investments')->everyMinute();
        // $schedule->command('app:update-usdt-investments')->withoutOverlapping()->runInBackground()->everyMinute();
        // $schedule->command('app:delete-expired-usdt-investments')->dailyAt($time);
    }   

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
