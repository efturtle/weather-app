<?php

namespace App\Providers;

use App\Providers\NoForecastsToday;
use App\Models\WeatherForecast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;

class SaveTodaysForecast
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Providers\NoForecastsToday  $event
     * @return void
     */
    public function handle(NoForecastsToday $event)
    {
        Http::post('http://127.0.0.1:8000/api/v1/weather', [
            'date' => $event->date,
        ]);
    }
}
