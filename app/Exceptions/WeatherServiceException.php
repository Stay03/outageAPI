<?php

namespace App\Exceptions;

use Exception;

class WeatherServiceException extends Exception
{
    /**
     * The coordinates that failed to fetch weather data.
     *
     * @var string
     */
    protected $coordinates;

    /**
     * Create a new weather service exception instance.
     *
     * @param  string  $message
     * @param  string  $coordinates
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct($message = "", $coordinates = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->coordinates = $coordinates;
    }

    /**
     * Get the coordinates that failed to fetch weather data.
     *
     * @return string
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }
}