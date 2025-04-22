<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\OutageResource;
use App\Models\Outage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class OutageController extends Controller
{
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location_id' => 'nullable|exists:locations,id',
            'weather_condition' => 'required|string',
            'temperature' => 'required|numeric|between:-50,60',
            'wind_speed' => 'required|numeric|min:0',
            'precipitation' => 'required|numeric|min:0',
            'is_holiday' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a new outage
        $outage = new Outage($request->all());
        $outage->user_id = $request->user()->id;
        
        // Calculate day_of_week automatically based on start_time
        $startTime = Carbon::parse($request->input('start_time'));
        $outage->day_of_week = $startTime->dayOfWeek;
        
        $outage->save();

        return response()->json([
            'message' => 'Outage created successfully',
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $outage = Outage::findOrFail($id);

        // Verify ownership
        $this->authorize('update', $outage);

        $validator = Validator::make($request->all(), [
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'location_id' => 'nullable|exists:locations,id',
            'weather_condition' => 'sometimes|required|string',
            'temperature' => 'sometimes|required|numeric|between:-50,60',
            'wind_speed' => 'sometimes|required|numeric|min:0',
            'precipitation' => 'sometimes|required|numeric|min:0',
            'is_holiday' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update the outage
        $outage->fill($request->all());

        // If start_time is changing, update day_of_week
        if ($request->has('start_time')) {
            $startTime = Carbon::parse($request->input('start_time'));
            $outage->day_of_week = $startTime->dayOfWeek;
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