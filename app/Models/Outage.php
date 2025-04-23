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
        // Additional weather fields
        'humidity',
        'pressure',
        'cloud',
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
        // Additional weather field casts
        'humidity' => 'integer',
        'pressure' => 'float',
        'cloud' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['duration', 'status'];

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
     * For ongoing outages (null end_time), returns elapsed time since start.
     * 
     * @return int|null
     */
    public function getDurationAttribute()
    {
        if (!$this->start_time) {
            return null;
        }
        
        if (!$this->end_time) {
            // If end_time is null, calculate duration from start_time to now
            return $this->start_time->diffInMinutes(Carbon::now());
        }
        
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Get the status of the outage.
     * 
     * @return string
     */
    public function getStatusAttribute()
    {
        return $this->end_time === null ? 'ongoing' : 'completed';
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
     * Scope a query to only include ongoing outages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOngoing($query)
    {
        return $query->whereNull('end_time');
    }

    /**
     * Scope a query to only include completed outages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('end_time');
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
                    ->where(function($query) use ($endDate) {
                        $query->where('end_time', '<=', $endDate)
                              ->orWhereNull('end_time');
                    });
    }

    /**
     * Scope a query to only include outages within duration range.
     * For ongoing outages, uses the current time to calculate duration.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $minMinutes
     * @param  int  $maxMinutes
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDurationBetween($query, $minMinutes, $maxMinutes)
    {
        return $query->where(function($query) use ($minMinutes, $maxMinutes) {
            // For completed outages
            $query->whereNotNull('end_time')
                  ->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, end_time) >= ?', [$minMinutes])
                  ->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, end_time) <= ?', [$maxMinutes]);
        })->orWhere(function($query) use ($minMinutes, $maxMinutes) {
            // For ongoing outages
            $query->whereNull('end_time')
                  ->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, NOW()) >= ?', [$minMinutes])
                  ->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, NOW()) <= ?', [$maxMinutes]);
        });
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