<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OutageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'start_time' => $this->start_time,
            'end_time' => $this->when($this->end_time, $this->end_time),
            'duration' => $this->duration, // This is a calculated attribute from the model
            'status' => $this->status, // New calculated attribute: 'ongoing' or 'completed'
            'location' => $this->when($this->location_id, function () {
                return [
                    'id' => $this->location->id,
                    'name' => $this->location->name,
                    'address' => $this->location->address,
                    'locality' => $this->location->locality,
                    'city' => $this->location->city,
                    'country' => $this->location->country,
                    'latitude' => $this->location->latitude,
                    'longitude' => $this->location->longitude,
                ];
            }),
            'weather' => [
                'condition' => $this->weather_condition,
                'temperature' => $this->temperature,
                'wind_speed' => $this->wind_speed,
                'precipitation' => $this->precipitation,
                // Additional weather data
                'humidity' => $this->when($this->humidity, $this->humidity),
                'pressure' => $this->when($this->pressure, $this->pressure),
                'cloud' => $this->when($this->cloud, $this->cloud),
            ],
            'day_of_week' => $this->day_of_week,
            'is_holiday' => $this->is_holiday,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}