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
            $query->where(function($q) use ($request) {
                $q->where('end_time', '<=', $request->input('end_date'))
                  ->orWhereNull('end_time');
            });
        }

        if ($request->has('duration_min')) {
            $query->durationBetween($request->input('duration_min'), $request->input('duration_max', PHP_INT_MAX));
        }

        if ($request->has('status')) {
            if ($request->input('status') === 'ongoing') {
                $query->ongoing();
            } elseif ($request->input('status') === 'completed') {
                $query->completed();
            }
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

        $message = $outage->end_time ? 'Outage created successfully' : 'Ongoing outage created successfully';

        return response()->json([
            'message' => $message,
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
     * End an ongoing outage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function end(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'end_time' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $outage = Outage::findOrFail($id);

        // Verify ownership
        $this->authorize('update', $outage);

        // Check if outage is already ended
        if ($outage->end_time !== null) {
            return response()->json([
                'message' => 'This outage has already been ended.',
                'outage' => new OutageResource($outage)
            ], 422);
        }

        // Validate that end_time is after start_time
        $endTime = Carbon::parse($request->input('end_time'));
        if ($endTime->isBefore($outage->start_time)) {
            return response()->json([
                'message' => 'End time must be after start time.',
                'errors' => ['end_time' => ['End time must be after start time.']]
            ], 422);
        }

        // Update the end time
        $outage->end_time = $request->input('end_time');
        $outage->save();

        return response()->json([
            'message' => 'Outage has been marked as ended',
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