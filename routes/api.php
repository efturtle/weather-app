<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

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

Route::get('weather', function(){
    $time = now()->subDays(5)->timestamp;
    // get weather from 5 days ago
    $response = Http::acceptJson()->get("https://api.openweathermap.org/data/2.5/onecall/timemachine?lat=51.5085&lon=-0.1257&dt={$time}&appid={app_id}");

    // daily reports of weather
    $response = Http::acceptJson()->get("https://api.openweathermap.org/data/2.5/onecall?lat=33.44&lon=-94.04&exclude=currently,minutely,hourly,alerts&appid={app_id}");
    return $response;
});
