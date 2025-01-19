<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CheckJWTToken;
use App\Models\Car;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CarController extends Controller
{
    public function __construct()
    {
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'brand' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'license_plate' => 'required|string|max:20|unique:cars',
                'rental_rate' => 'required|numeric',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized. Token invalid or not provided.'], 401);
            }

            // dd($request->hasFile('photo'));

            $carData = [
                'brand' => strtolower($request->brand),
                'model' => strtolower($request->model),
                'license_plate' => strtoupper($request->license_plate),
                'rental_rate' => $request->rental_rate,
                'owner_id' => $user->id,
            ];

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $filename = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('photos'), $filename);
                $carData['photo'] = 'photos/' . $filename;
            }

            $car = Car::create($carData);

            return response()->json([
                'status' => 'success',
                'message' => '',
                'data' => $car,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'brand' => 'nullable|string|max:255',
                'model' => 'nullable|string|max:255',
                'license_plate' => 'nullable|string|max:20|unique:cars,license_plate,' . $id,
                'rental_rate' => 'nullable|numeric',
                'available' => 'nullable|boolean',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Add this line
            ]);

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized. Token invalid or not provided.'], 401);
            }

            $car = Car::find($id);

            if (!$car || $car->owner_id !== $user->id) {
                return response()->json([
                    'error' => 'Car not found or unauthorized access.',
                ], 403);
            }

            $carData = [
                'brand' => $request->brand ? strtolower($request->brand) : $car->brand,
                'model' => $request->model ? strtolower($request->model) : $car->model,
                'license_plate' => $request->license_plate ? strtoupper($request->license_plate) : $car->license_plate,
                'rental_rate' => $request->rental_rate ?? $car->rental_rate,
                'available' => $request->available ?? $car->available,
            ];

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $filename = time() . '_' . $photo->getClientOriginalName();
                $photo->move(public_path('photos'), $filename);
                $carData['photo'] = 'photos/' . $filename;
            }

            $car->update($carData);

            return response()->json([
                'message' => 'Car updated successfully.',
                'data' => $car,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $searchQuery = $request->input('q');

            $cars = Car::where('owner_id', '=', JWTAuth::parseToken()->authenticate()->id)
                ->when($searchQuery, function ($q) use ($searchQuery) {
                    $q->whereRaw('LOWER(brand) LIKE ?', ['%' . strtolower($searchQuery) . '%'])
                    ->orWhereRaw('LOWER(model) LIKE ?', ['%' . strtolower($searchQuery) . '%']);
                })
                ->orderBy('available', 'desc')
                ->get();

            foreach ($cars as $car) {
                if ($car->photo) {
                    $car->photo = asset($car->photo);
                }
            }

            if ($cars->isEmpty()) {
                return response()->json([
                    'message' => 'No available cars found',
                    'data' => [],
                    'status' => 'error'
                ], 404);
            }

            return response()->json([
                'message' => 'Available cars retrieved successfully',
                'data' => $cars,
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve available cars',
                'data' => null,
                'status' => 'error',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $car = Car::findOrFail($id);
        return response()->json($car);
    }

    public function getAvailableCars(Request $request)
    {
        try {
            $searchQuery = $request->input('q');

            $cars = Car::where('owner_id', '!=', JWTAuth::parseToken()->authenticate()->id)
                ->when($searchQuery, function ($q) use ($searchQuery) {
                    $q->whereRaw('LOWER(brand) LIKE ?', ['%' . strtolower($searchQuery) . '%'])
                    ->orWhereRaw('LOWER(model) LIKE ?', ['%' . strtolower($searchQuery) . '%']);
                })
                ->orderBy('available', 'desc')
                ->get();

            foreach ($cars as $car) {
                if ($car->photo) {
                    $car->photo = asset($car->photo);
                }
            }


            if ($cars->isEmpty()) {
                return response()->json([
                    'message' => 'No available cars found',
                    'data' => [],
                    'status' => 'error'
                ], 404);
            }

            return response()->json([
                'message' => 'Available cars retrieved successfully',
                'data' => $cars,
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve available cars',
                'data' => null,
                'status' => 'error',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $searchQuery = $request->input('q');

        $availableCars = Car::where('available', true)
                ->where('owner_id', '!=', JWTAuth::parseToken()->authenticate()->id)
                ->whereDoesntHave('rentals', function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q2) use ($startDate, $endDate) {
                                $q2->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
                    });
                })
                ->when($searchQuery, function ($query) use ($searchQuery) {
                    $query->whereRaw('LOWER(brand) LIKE ?', ['%' . strtolower($searchQuery) . '%'])
                        ->orWhereRaw('LOWER(model) LIKE ?', ['%' . strtolower($searchQuery) . '%']);
                })->with('owner')
                ->get();

        foreach ($availableCars as $car) {
            if ($car->photo) {
                $car->photo = asset($car->photo);
            }
        }

        if ($availableCars->isEmpty()) {
            return response()->json([
                'message' => 'No cars available for the selected dates.',
                'data' => [],
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'Cars available for the selected dates.',
            'data' => $availableCars,
            'status' => 'success'
        ]);
    }

    public function destroy($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $car = Car::find($id);

            if (!$car || $car->owner_id !== $user->id) {
                return response()->json([
                    'message' => 'Car not found or unauthorized access.',
                    'status' => 'error'
                ], 403);
            }

            $car->delete();

            return response()->json([
                'message' => 'Car deleted successfully.',
                'status' => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete car.',
                'status' => 'error',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function updateAvailability(Request $request, $id)
    {
        $car = Car::findOrFail($id);
        $car->available = $request->available;
        $car->save();

        return response()->json($car);
    }
}
