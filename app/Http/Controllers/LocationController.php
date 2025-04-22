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
        $query = Location::query();

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

        // Check for duplicate locations
        $existingLocation = Location::where('latitude', $request->input('latitude'))
                                    ->where('longitude', $request->input('longitude'))
                                    ->first();

        if ($existingLocation) {
            return response()->json([
                'message' => 'A location with these coordinates already exists',
                'location' => new LocationResource($existingLocation)
            ], 409);
        }

        // Create a new location
        $location = Location::create($request->all());

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

        // If coordinates are changing, check for duplicates
        if ($request->has('latitude') || $request->has('longitude')) {
            $lat = $request->input('latitude', $location->latitude);
            $lng = $request->input('longitude', $location->longitude);
            
            $existingLocation = Location::where('id', '!=', $id)
                                        ->where('latitude', $lat)
                                        ->where('longitude', $lng)
                                        ->first();
            
            if ($existingLocation) {
                return response()->json([
                    'message' => 'A location with these coordinates already exists',
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