<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'locality',
        'city',
        'country',
        'latitude',
        'longitude',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Get the user that owns the location.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get all of the outages for the location.
     */
    public function outages()
    {
        return $this->hasMany(Outage::class);
    }

    /**
     * Scope a query to only include locations in a specific city.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $city
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope a query to only include locations in a specific locality.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $locality
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInLocality($query, $locality)
    {
        return $query->where('locality', $locality);
    }

    /**
     * Scope a query to only include locations in a specific country.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $country
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCountry($query, $country)
    {
        return $query->where('country', $country);
    }
    
    /**
     * Find locations within a specified radius (in kilometers) from a point.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $lat  Latitude of the center point
     * @param  float  $lng  Longitude of the center point
     * @param  float  $radius  Radius in kilometers
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithinRadius($query, $lat, $lng, $radius)
    {
        // Using the Haversine formula directly in SQLite
        // 6371 is Earth's radius in kilometers
        return $query->whereRaw('
            (6371 * acos(
                cos(radians(?)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * 
                sin(radians(latitude))
            )) < ?
        ', [$lat, $lng, $lat, $radius]);
    }

    /**
     * Get the full formatted address.
     * 
     * @return string
     */
    public function getFullAddressAttribute()
    {
        $parts = [$this->address];
        
        if ($this->locality) {
            $parts[] = $this->locality;
        }
        
        if ($this->city) {
            $parts[] = $this->city;
        }
        
        if ($this->country) {
            $parts[] = $this->country;
        }
        
        return implode(', ', $parts);
    }
}