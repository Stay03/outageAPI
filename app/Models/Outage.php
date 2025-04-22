<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Outage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'location_id',
        'weather_condition',
        'temperature',
        'wind_speed',
        'precipitation',
        'day_of_week',
        'is_holiday',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'temperature' => 'float',
        'wind_speed' => 'float',
        'precipitation' => 'float',
        'day_of_week' => 'integer',
        'is_holiday' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['duration'];

    /**
     * Get the user that owns the outage.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location associated with the outage.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Calculate the duration of the outage in minutes.
     * 
     * @return int
     */
    public function getDurationAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Set the day of week based on start time.
     *
     * @param  string  $value
     * @return void
     */
    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = $value;
        
        // Auto-populate day_of_week based on start_time
        if ($value) {
            $date = Carbon::parse($value);
            $this->attributes['day_of_week'] = $date->dayOfWeek;
        }
    }

    /**
     * Scope a query to only include outages between given dates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where('start_time', '>=', $startDate)
                    ->where('end_time', '<=', $endDate);
    }

    /**
     * Scope a query to only include outages within duration range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $minMinutes
     * @param  int  $maxMinutes
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDurationBetween($query, $minMinutes, $maxMinutes)
    {
        return $query->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, end_time) >= ?', [$minMinutes])
                    ->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, end_time) <= ?', [$maxMinutes]);
    }

    /**
     * Scope a query to filter by weather condition.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $condition
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWeatherCondition($query, $condition)
    {
        return $query->where('weather_condition', $condition);
    }

    /**
     * Scope a query to filter by day of week.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $dayOfWeek
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDayOfWeek($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope a query to filter by holiday status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  bool  $isHoliday
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHoliday($query, $isHoliday)
    {
        return $query->where('is_holiday', $isHoliday);
    }
}