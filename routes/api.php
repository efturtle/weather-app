<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use App\Http\Controllers\WeatherForecastController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('tester', function(){
    $weatherKey = env('WEATHER_KEY');
    $time = now()->subDays(5)->timestamp;
    // get weather from 5 days ago
    $response = Http::acceptJson()->get("https://api.openweathermap.org/data/2.5/onecall/timemachine?lat=51.5085&lon=-0.1257&dt={$time}&appid={$weatherKey}");

    // daily reports of weather
    // $response = Http::acceptJson()->get("https://api.openweathermap.org/data/2.5/onecall?lat=33.44&lon=-94.04&units=metric&exclude=currently,minutely,hourly,alerts&appid={$weatherKey}");
    // $response = Http::get("https://api.openweathermap.org/data/2.5/onecall?lat=33.44&lon=-94.04&units=metric&exclude=currently,minutely,hourly,alerts&appid={$weatherKey}");
    // $timeFromTimestamp = Carbon::createFromTimeStamp(1651255200)->toDateTimeString();
    // $data = json_decode($response);
    // return $data->daily[0];
    return $response;
});

Route::resource('/v1/weather', WeatherForecastController::class)->only([
    'index', 'store', 'show'
]);

Route::put('v1/weather', [WeatherForecastController::class, 'updateByDate']);


