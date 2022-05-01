<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WeatherForeCastTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_indexForecast()
    {
        $response = $this->get('/api/v1/weather');

        $response->assertStatus(200);
    }

    public function test_storeForecast()
    {
        $response = $this->postJson('/api/v1/weather', [
            'date' => '2022-04-27',
        ]);

        $response->assertStatus(201)
        ->assertJson([
            'created' => true,
        ]);
    }

    public function test_updateForecast()
    {
        $response = $this->putJson('/api/v1/weather', [
            'date' => '2022-04-30',
        ]);

        $response->assertStatus(200)
        ->assertJson([
            'updated' => true,
        ]);
    }
}
