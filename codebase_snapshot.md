# Codebase Documentation

{
  "Extraction Date": "2025-04-23 08:40:19",
  "Include Paths": [
    "app/Models/User.php",
    "app/Models/Outage.php",
    "app/Models/Location.php",
    "app/Services/WeatherService.php",
    "app/Http/Resources/CollectionResource.php",
    "app/Http/Resources/UserResource.php",
    "app/Http/Resources/OutageResource.php",
    "app/Http/Resources/LocationResource.php",
    "app/Http/Requests/OutageRequest.php",
    "app/Http/Controllers/AuthController.php",
    "app/Http/Controllers/OutageController.php",
    "app/Http/Controllers/LocationController.php",
    "app/Exceptions/WeatherServiceException.php",
    "app/Policies/OutagePolicy.php",
    "app/Policies/LocationPolicy.php",
    "config/services.php",
    "routes/api.php"
  ]
}

### app/Models/User.php
```
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the outages for the user.
     */
    public function outages()
    {
        return $this->hasMany(Outage::class);
    }
    
    /**
     * Get the locations for the user.
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}
```

### app/Models/Outage.php
```
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
```

### app/Models/Location.php
```
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
```

### app/Services/WeatherService.php
```
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
```

### app/Http/Resources/CollectionResource.php
```
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CollectionResource extends ResourceCollection
{
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'pagination' => [
                'total' => $this->resource->total(),
                'count' => $this->resource->count(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'total_pages' => $this->resource->lastPage(),
                'links' => [
                    'first' => $this->resource->url(1),
                    'last' => $this->resource->url($this->resource->lastPage()),
                    'prev' => $this->resource->previousPageUrl(),
                    'next' => $this->resource->nextPageUrl(),
                ],
            ],
        ];
    }
}
```

### app/Http/Resources/UserResource.php
```
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### app/Http/Resources/OutageResource.php
```
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
            'end_time' => $this->end_time,
            'duration' => $this->duration, // This is a calculated attribute from the model
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
```

### app/Http/Resources/LocationResource.php
```
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'locality' => $this->when($this->locality, $this->locality),
            'city' => $this->when($this->city, $this->city),
            'country' => $this->when($this->country, $this->country),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'full_address' => $this->full_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### app/Http/Requests/OutageRequest.php
```
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OutageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization will be handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location_id' => 'required|exists:locations,id',
            'is_holiday' => 'required|boolean',
        ];

        // For updates, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(function ($rule) {
                return str_replace('required', 'sometimes|required', $rule);
            }, $rules);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'location_id.required' => 'A location is required to fetch weather data.',
            'location_id.exists' => 'The selected location is invalid.',
            'start_time.required' => 'Please specify when the outage started.',
            'end_time.required' => 'Please specify when the outage ended.',
            'end_time.after' => 'The end time must be after the start time.',
        ];
    }
}
```

### app/Http/Controllers/AuthController.php
```
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create token for the newly registered user
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Log in a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if user exists
        $user = User::where('email', $request->email)->first();

        // Validate the user credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke previous tokens if requested
        if ($request->input('logout_others', false)) {
            $user->tokens()->delete();
        }

        // Create a new access token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Log out a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        // Revoke the current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => new UserResource($request->user())
        ]);
    }
}
```

### app/Http/Controllers/OutageController.php
```
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\OutageRequest;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\OutageResource;
use App\Models\Location;
use App\Models\Outage;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OutageController extends Controller
{
    /**
     * The weather service instance.
     *
     * @var \App\Services\WeatherService
     */
    protected $weatherService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\WeatherService  $weatherService
     * @return void
     */
    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Display a listing of outages for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = $request->user()->outages();

        // Apply filters
        if ($request->has('start_date')) {
            $query->where('start_time', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('end_time', '<=', $request->input('end_date'));
        }

        if ($request->has('duration_min')) {
            $query->durationBetween($request->input('duration_min'), $request->input('duration_max', PHP_INT_MAX));
        }

        if ($request->has('weather_condition')) {
            $query->weatherCondition($request->input('weather_condition'));
        }

        if ($request->has('temperature_min')) {
            $query->where('temperature', '>=', $request->input('temperature_min'));
        }

        if ($request->has('temperature_max')) {
            $query->where('temperature', '<=', $request->input('temperature_max'));
        }

        if ($request->has('wind_speed_min')) {
            $query->where('wind_speed', '>=', $request->input('wind_speed_min'));
        }

        if ($request->has('is_holiday')) {
            $query->holiday(filter_var($request->input('is_holiday'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('day_of_week')) {
            $query->dayOfWeek($request->input('day_of_week'));
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'start_time');
        $order = $request->input('order', 'desc');
        $query->orderBy($sortBy, $order);

        // Apply pagination
        $perPage = min(100, $request->input('per_page', 15));
        $page = $request->input('page', 1);
        $outages = $query->paginate($perPage, ['*'], 'page', $page);

        return new CollectionResource($outages);
    }

    /**
     * Store a newly created outage in storage.
     *
     * @param  \App\Http\Requests\OutageRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(OutageRequest $request)
    {
        // Create a new outage with validated data
        $outage = new Outage($request->validated());
        $outage->user_id = $request->user()->id;
        
        // Calculate day_of_week automatically based on start_time
        $startTime = Carbon::parse($request->input('start_time'));
        $outage->day_of_week = $startTime->dayOfWeek;
        
        // Get the location to fetch weather data
        $location = Location::findOrFail($request->input('location_id'));
        
        // Fetch weather data using the location's coordinates
        $weatherData = $this->weatherService->getCurrentWeather($location->latitude, $location->longitude);
        
        if ($weatherData) {
            // Fill the outage with weather data
            $outage->fill($weatherData);
        } else {
            // If weather data fetching fails, return an error
            return response()->json([
                'message' => 'Unable to fetch weather data. Please try again later.',
            ], 503); // 503 Service Unavailable
        }
        
        $outage->save();

        return response()->json([
            'message' => 'Outage created successfully with automatically fetched weather data',
            'outage' => new OutageResource($outage)
        ], 201);
    }

    /**
     * Display the specified outage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $outage = Outage::findOrFail($id);

        // Verify ownership
        $this->authorize('view', $outage);

        return response()->json([
            'outage' => new OutageResource($outage)
        ]);
    }

    /**
     * Update the specified outage in storage.
     *
     * @param  \App\Http\Requests\OutageRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(OutageRequest $request, $id)
    {
        $outage = Outage::findOrFail($id);

        // Verify ownership
        $this->authorize('update', $outage);

        // Update the outage with validated data
        $outage->fill($request->validated());

        // If start_time is changing, update day_of_week
        if ($request->has('start_time')) {
            $startTime = Carbon::parse($request->input('start_time'));
            $outage->day_of_week = $startTime->dayOfWeek;
        }
        
        // If location is changing, update weather data
        if ($request->has('location_id')) {
            $location = Location::findOrFail($request->input('location_id'));
            
            // Fetch weather data using the location's coordinates
            $weatherData = $this->weatherService->getCurrentWeather($location->latitude, $location->longitude);
            
            if ($weatherData) {
                // Fill the outage with weather data
                $outage->fill($weatherData);
            } else {
                // If weather data fetching fails, return an error
                return response()->json([
                    'message' => 'Unable to fetch weather data. Please try again later.',
                ], 503); // 503 Service Unavailable
            }
        }
        
        $outage->save();

        return response()->json([
            'message' => 'Outage updated successfully',
            'outage' => new OutageResource($outage)
        ]);
    }

    /**
     * Remove the specified outage from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $outage = Outage::findOrFail($id);

        // Verify ownership
        $this->authorize('delete', $outage);

        $outage->delete();

        return response()->json([
            'message' => 'Outage deleted successfully'
        ]);
    }
}
```

### app/Http/Controllers/LocationController.php
```
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Only get locations belonging to the authenticated user
        $query = $request->user()->locations();

        // Apply filters
        if ($request->has('city')) {
            $query->inCity($request->input('city'));
        }

        if ($request->has('locality')) {
            $query->inLocality($request->input('locality'));
        }

        if ($request->has('country')) {
            $query->inCountry($request->input('country'));
        }

        // Apply radius search if latitude, longitude, and radius are provided
        if ($request->has(['latitude', 'longitude', 'radius'])) {
            $query->withinRadius(
                $request->input('latitude'),
                $request->input('longitude'),
                $request->input('radius')
            );
        }

        // Apply search by name or address
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('address', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'name');
        $order = $request->input('order', 'asc');
        $query->orderBy($sortBy, $order);

        // Apply pagination
        $perPage = min(100, $request->input('per_page', 15));
        $page = $request->input('page', 1);
        $locations = $query->paginate($perPage, ['*'], 'page', $page);

        return new CollectionResource($locations);
    }

    /**
     * Store a newly created location in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'locality' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for duplicate locations for the current user
        $existingLocation = $request->user()->locations()
                                    ->where('latitude', $request->input('latitude'))
                                    ->where('longitude', $request->input('longitude'))
                                    ->first();

        if ($existingLocation) {
            return response()->json([
                'message' => 'A location with these coordinates already exists in your account',
                'location' => new LocationResource($existingLocation)
            ], 409);
        }

        // Create a new location and assign the user_id
        $location = new Location($request->all());
        $location->user_id = $request->user()->id;
        $location->save();

        return response()->json([
            'message' => 'Location created successfully',
            'location' => new LocationResource($location)
        ], 201);
    }

    /**
     * Display the specified location.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $location = Location::findOrFail($id);

        // Verify authorization
        $this->authorize('view', $location);

        return response()->json([
            'location' => new LocationResource($location)
        ]);
    }

    /**
     * Update the specified location in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        // Verify authorization
        $this->authorize('update', $location);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string|max:255',
            'locality' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If coordinates are changing, check for duplicates within the user's locations
        if ($request->has('latitude') || $request->has('longitude')) {
            $lat = $request->input('latitude', $location->latitude);
            $lng = $request->input('longitude', $location->longitude);
            
            $existingLocation = $request->user()->locations()
                                        ->where('id', '!=', $id)
                                        ->where('latitude', $lat)
                                        ->where('longitude', $lng)
                                        ->first();
            
            if ($existingLocation) {
                return response()->json([
                    'message' => 'A location with these coordinates already exists in your account',
                    'location' => new LocationResource($existingLocation)
                ], 409);
            }
        }

        // Update the location
        $location->update($request->all());

        return response()->json([
            'message' => 'Location updated successfully',
            'location' => new LocationResource($location)
        ]);
    }

    /**
     * Remove the specified location from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $location = Location::findOrFail($id);

        // Verify authorization
        $this->authorize('delete', $location);

        // Check for associated outages (also handled in policy)
        if ($location->outages()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete location with associated outages'
            ], 409);
        }

        $location->delete();

        return response()->json([
            'message' => 'Location deleted successfully'
        ]);
    }
}
```

### app/Exceptions/WeatherServiceException.php
```
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
```

### app/Policies/OutagePolicy.php
```
<?php

namespace App\Policies;

use App\Models\Outage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OutagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true; // Users can view their own outages
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Outage  $outage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Outage $outage)
    {
        return $user->id === $outage->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true; // Authenticated users can create outages
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Outage  $outage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Outage $outage)
    {
        return $user->id === $outage->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Outage  $outage
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Outage $outage)
    {
        return $user->id === $outage->user_id;
    }
}
```

### app/Policies/LocationPolicy.php
```
<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true; // Users can view their own locations (filtered in controller)
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Location $location)
    {
        // User can only view their own locations
        return $user->id === $location->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true; // Authenticated users can create locations
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Location $location)
    {
        // User can only update their own locations
        return $user->id === $location->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Location  $location
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Location $location)
    {
        // Check if location has any associated outages
        if ($location->outages()->count() > 0) {
            return false;
        }
        
        // User can only delete their own locations
        return $user->id === $location->user_id;
    }
}
```

### config/services.php
```
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'weather' => [
        'key' => env('WEATHER_API_KEY'),
        'url' => env('WEATHER_API_URL', 'http://api.weatherapi.com/v1'),
    ],

];

```

### routes/api.php
```
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OutageController;
use App\Http\Controllers\LocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// V1 API Routes
Route::prefix('v1')->group(function () {
    // Auth routes (public)
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        
        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
        });
    });
    
    // Protected routes with rate limiting
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // Outage routes
        Route::apiResource('outages', OutageController::class);
        
        // Location routes
        Route::apiResource('locations', LocationController::class);
        
        // Analytics routes will be added here later
    });
});

// Fallback for undefined API routes
Route::fallback(function() {
    return response()->json([
        'message' => 'Endpoint not found. If error persists, contact info@ourapp.com'
    ], 404);
});
```

