<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class WeatherService
{
    /**
     * The Weather API base URL.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * The Weather API key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Create a new weather service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->baseUrl = config('services.weather.url');
        $this->apiKey = config('services.weather.key');
    }

    /**
     * Get current weather data for a specific location.
     *
     * @param float $latitude
     * @param float $longitude
     * @return array|null
     */
    public function getCurrentWeather($latitude, $longitude)
    {
        try {
            $response = Http::get("{$this->baseUrl}/current.json", [
                'key' => $this->apiKey,
                'q' => "{$latitude},{$longitude}",
                'aqi' => 'no',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatWeatherData($data);
            } else {
                Log::error('Weather API error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'coordinates' => "{$latitude},{$longitude}"
                ]);
                return null;
            }
        } catch (ConnectionException $e) {
            Log::error('Weather API connection error', [
                'message' => $e->getMessage(),
                'coordinates' => "{$latitude},{$longitude}"
            ]);
            return null;
        }
    }

    /**
     * Format the weather API response into our application format.
     *
     * @param array $data
     * @return array
     */
    protected function formatWeatherData($data)
    {
        // Map the API response to our application's format
        return [
            'weather_condition' => $data['current']['condition']['text'],
            'temperature' => $data['current']['temp_c'],
            'wind_speed' => $data['current']['wind_kph'],
            'precipitation' => $data['current']['precip_mm'],
            // Additional fields that might be useful
            'humidity' => $data['current']['humidity'],
            'pressure' => $data['current']['pressure_mb'],
            'cloud' => $data['current']['cloud'],
        ];
    }
}