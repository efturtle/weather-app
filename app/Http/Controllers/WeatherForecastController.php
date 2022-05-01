<?php

namespace App\Http\Controllers;

use App\Models\WeatherForecast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\GenerateForecastOFToday;
use App\Jobs\StoreWeatherForecast;

class WeatherForecastController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json([
            'weather' => WeatherForecast::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate the incoming request
        $validator = Validator::make($request->all(), [
            'date' => 'required|date-format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'invalidDate' => true,
            ], 400);
        }

        $inputDate = Carbon::createFromFormat('Y-m-d', $request->date);

        // query the database for this date
        $forecastsFromRequestDate = DB::table('weather_forecasts')->select('city', 'temperature', 'date', 'description')->where('date', $inputDate->format('Y-m-d'))->get();

        if ($forecastsFromRequestDate->count() > 0) {
            return response()->json([
                'weatherForecasts' => $forecastsFromRequestDate
            ], 200);
        }

        $weatherKey = env('WEATHER_KEY');

        $weatherByCities = [];
        $weatherForecasts = [];
        $filteredWeather = [];

        $diffInDays = now()->diffInDays($inputDate);

        if ($this->isDateBetweenValidDates($inputDate)) {

            // PAST
            if ($inputDate->isPast()) {

                $timestamp = now()->subDays($diffInDays)->timestamp;
                // get data from each city
                for ($i=0; $i < 5; $i++) {
                    $latitudeLongitud = $this->getLatitudeLongitud($i);
                    $data = json_decode(Http::get("https://api.openweathermap.org/data/2.5/onecall/timemachine?lat={$latitudeLongitud[0]}&lon={$latitudeLongitud[1]}&units=metric&dt={$timestamp}&appid={$weatherKey}"));
                    array_push($weatherByCities, $data);
                }
                // create the models
                foreach ($weatherByCities as $key => $city) {

                    $cityWeather = [
                        'city' => $city->timezone,
                        'temperature' => $city->current->temp,
                        'date' => Carbon::createFromTimestamp($city->current->dt)->format('Y-m-d'),
                        'description' => $city->current->weather[0]->description,
                        'latitude' => $city->lat,
                        'longitud' => $city->lon,
                    ];

                    StoreWeatherForecast::dispatch($cityWeather);

                    array_push(
                        $weatherForecasts,
                        $cityWeather
                    );
                }
            }


            // FUTURE
            if ($inputDate->isFuture()) {
                $timestamp = now()->addDays($diffInDays)->timestamp;

                // get data from each city
                for ($i=0; $i < 5; $i++) {
                    $latitudeLongitud = $this->getLatitudeLongitud($i);
                    $data = json_decode(Http::get("https://api.openweathermap.org/data/2.5/onecall?lat={$latitudeLongitud[0]}&lon={$latitudeLongitud[1]}&units=metric&exclude=minutely,hourly,alerts&appid={$weatherKey}"));

                    // push only data from the difference in day position of array
                    array_push($weatherByCities, $data->daily[$diffInDays]);
                }
                // create the models
                foreach ($weatherByCities as $key => $city) {
                    $latitudeLongitud = $this->getLatitudeLongitud($key);

                    $cityWeather = [
                        'city' => $this->getCityName($key),
                        'temperature' => $city->temp->day,
                        'date' => Carbon::createFromTimestamp($city->dt)->format('Y-m-d'),
                        'description' => $city->weather[0]->description,
                        'latitude' => $latitudeLongitud[0],
                        'longitud' => $latitudeLongitud[1],
                    ];

                    StoreWeatherForecast::dispatch($cityWeather);

                    // remove items from array for json response
                    array_push(
                        $weatherForecasts,
                        $cityWeather
                    );
                }

            }

            return response()->json([
                'created' => true,
                'weatherForecasts' => $weatherForecasts,
            ], 201);

        }

        return response()->json([
            'error' => 'Select Date between 5 days prior and 7 days future'
        ]);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WeatherForecast  $weatherForecast
     * @return \Illuminate\Http\Response
     */
    public function show(WeatherForecast $weatherForecast)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WeatherForecast  $weatherForecast
     * @return \Illuminate\Http\Response
     */
    public function updateByDate(Request $request)
    {
        if (!$this->isRequestDateValid($request)) {
            return response()->json([
                'invalidDate' => true,
            ], 400);
        }

        $inputDate = Carbon::createFromFormat('Y-m-d', $request->date);

        // check if date is from today
        if (!$inputDate->isToday()) {
            return response()->json([
                'error' => 'Can only update todays weather forecast',
            ]);
        }

        $forecasts = WeatherForecast::where('date', $inputDate->format('Y-m-d'))->get();

        // check if there is any results
        if ($forecasts->isEmpty()) {
            event(new \App\Providers\NoForecastsToday(now()->format('Y-m-d')));

            return response()->json([
                'noResults' => true,
                'forecasts' => WeatherForecast::where('date', now()->format('Y-m-d'))->get()
            ]);
        }

        // reach for the weather api
        $weatherKey = env('WEATHER_KEY');
        $filteredWeather = [];
        $weatherByCities = [];
        // get data from each city
        for ($i=0; $i < 5; $i++) {
            $latitudeLongitud = $this->getLatitudeLongitud($i);
            $data = json_decode(Http::get("https://api.openweathermap.org/data/2.5/weather?lat={$latitudeLongitud[0]}&lon={$latitudeLongitud[1]}&units=metric&appid={$weatherKey}"));
            array_push($weatherByCities, $data);
        }

        // run a foreach on the forecasts in database and update the temperature
        foreach ($weatherByCities as $key => $city) {
            foreach ($forecasts as $key => $forecast) {
                if ($forecast->latitude == $city->coord->lat && $forecast->longitud == $city->coord->lon) {

                    // updates the temperature
                    $forecast->temperature = $city->main->temp;
                    $forecast->save();
                }
            }

            array_push(
                $filteredWeather,
                $this->getFilteredArray($forecast)
            );
        }

        return response()->json([
            'updated' => true,
            'forecasts' => $filteredWeather
        ]);
    }

    protected function getFilteredArray($forecast)
    {
        $collection = collect($forecast);
        $filtered = $collection->except(['id', 'created_at', 'updated_at']);
        return $filtered->all();
    }

    protected function getCityName($cityId)
    {
        switch ($cityId) {
            case 0:
                return  'America/New_York';
                break;
            case 1:
                return 'Europe/London';
                break;
            case 2:
                return 'Europe/Paris';
                break;
            case 3:
                return 'Europe/Berlin';
                break;
            case 4:
                return 'Asia/Tokyo';
                break;

            default:
                return 'not registered city';
                break;
        }
    }

    public function getLatitudeLongitud($city)
    {
        switch ($city) {
            case 0:
                return ['40.7143', '-74.006'];
                break;
            case 1:
                return ['51.5085', '-0.1257'];
                break;
            case 2:
                return ['48.8534', '2.3488'];
                break;
            case 3:
                return ['52.5244', '13.4105'];
                break;
            case 4:
                return ['35.6895', '139.6917'];
                break;
            default:
                return 'wrong city';
                break;
        }
    }

    protected function isRequestDateValid($request)
    {
        // validate the request
        $validator = Validator::make($request->all(), [
            'date' => 'required|date-format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return false;
        }
        return true;
    }

    protected function isDateBetweenValidDates($date)
    {
        $result = $date->between(
            now()->subDays(5),
            now()->addDays(7)
        );
        return $result;
    }
}
