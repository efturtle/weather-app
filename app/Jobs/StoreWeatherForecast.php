<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WeatherForecast;

class StoreWeatherForecast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $weatherForecast;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($weatherForecast)
    {
        $this->weatherForecast = $weatherForecast;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        WeatherForecast::create($this->weatherForecast);
    }
}
