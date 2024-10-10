<?php

namespace App\Console;

use App\Console\Commands\updateCustomersCorporateBusinessInfo;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /*| to start using laravel schedule paste this command to cron
* * * * * cd /path_to_your_project && /usr/local/bin/docker-compose exec -T panel php artisan schedule:run >> /dev/null 2>&1
|*/

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('app:update-customers-corporate-business-info')->cron('* * * * *');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
