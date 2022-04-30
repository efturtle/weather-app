<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WeatherForecastFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'city' => $this->faker->city(),
            'temperature' => $this->faker->numberBetween(20, 30),
            'date' => $this->faker->date(),
            'description' => $this->faker->word(),
            'latitude' => 'lol',
            'longitud' => 'lol'
        ];
    }
}
