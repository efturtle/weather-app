<?php

namespace App\Http\Controllers;

use App\Models\WeatherForecast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

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

        // make sure the date is between five days ago and 7 days in the future
        $isBetweenValidDates = $inputDate->between(
            now()->subDays(5),
            now()->addDays(7)
        );

        $weatherByCities = [];
        $weatherForecasts = [];

        $diffInDays = now()->diffInDays($inputDate);

        if ($isBetweenValidDates) {

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
                foreach ($weatherByCities as $city) {
                    array_push($weatherForecasts,
                        WeatherForecast::create([
                            'city' => $city->timezone,
                            'temperature' => $city->current->temp,
                            'date' => Carbon::createFromTimestamp($city->current->dt)->format('Y-m-d'),
                            'description' => $city->current->weather[0]->description,
                            'latitude' => $city->lat,
                            'longitud' => $city->lon,
                        ])
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
                    // push to the array and create
                    array_push($weatherForecasts,
                        WeatherForecast::create([
                            'city' => $this->getCityName($key),
                            'temperature' => $city->temp->day,
                            'date' => Carbon::createFromTimestamp($city->dt)->format('Y-m-d'),
                            'description' => $city->weather[0]->description,
                            'latitude' => $latitudeLongitud[0],
                            'longitude' => $latitudeLongitud[1],
                        ])
                    );
                }

            }

            return response()->json([
                'created' => true,
                'weatherForecasts' => $weatherForecasts,
            ], 201);

        }
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
        // validate the request
        $validator = Validator::make($request->all(), [
            'date' => 'required|date-format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'invalidDate' => true,
            ], 400);
        }

        $inputDate = Carbon::createFromFormat('Y-m-d', $request->date);

        // check if date is from today
        if (!$inputDate->isToday()) {
            return response()->json([
                'date-is-not-today' => true,
            ]);
        }

        $forecasts = WeatherForecast::where('date', $inputDate->format('Y-m-d'))
        ->select('city', 'temperature', 'date', 'description', 'latitude', 'longitud')
        ->get();

        // check if there is any results
        if ($forecasts->isEmpty()) {
            return response()->json([
                'noResults' => true,
            ]);
        }

        // reach for the weather api
        $weatherKey = env('WEATHER_KEY');
        $weatherForecasts = [];
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

                    // updates only the temperature
                    $forecast->update([
                        'temperature' => $city->main->temp,
                    ]);
                }
            }
        }

        return response()->json([
            'updated' => true,
            'forecasts' => $forecasts
        ]);
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

    protected function getLatitudeLongitud($city)
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
}
