<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Job;
use Log;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
            //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        $schedule->call(function () {
            $Ordercontroller = new OrderController;
            $Productcontroller = new ProductController;
            // Log::info("Cron running " . date('H:i:s'));
              $jobs = Job::get();
              if ($jobs->toArray()) {
              foreach ($jobs as $job) {
                if($job->api == 'order'){
                    $result = $Ordercontroller->dispatchOrderByCronJob($job);
                    if($result){
                        $job->delete();
                    }
                }elseif($job->api == 'product'){
                    $result = $Productcontroller->dispatchProductByCronJob($job);
                    if($result){
                        $job->delete();
                    }
                }
              }
          }
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

}
