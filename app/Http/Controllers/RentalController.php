<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Car;
use App\Models\CarReturn;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class RentalController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'car_id' => 'required|exists:cars,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:today',
            ]);

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized. Token invalid or not provided.'], 401);
            }

            $car = Car::find($validated['car_id']);
            $existingRental = Rental::where('car_id', $car->id)
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                          ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                          ->orWhere(function ($q) use ($validated) {
                              $q->where('start_date', '<=', $validated['start_date'])
                                ->where('end_date', '>=', $validated['end_date']);
                          });
                })
                ->exists();

            if ($existingRental) {
                return response()->json([
                    'message' => 'Car is not available for the selected dates.',
                    'status' => 'error',
                ], 400);
            }

            $rental = Rental::create([
                'user_id' => $user->id,
                'car_id' => $validated['car_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'ongoing',
            ]);

            return response()->json([
                'message' => 'Car booked successfully.',
                'data' => $rental,
                'status' => 'success',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'status' => 'error',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to book car.',
                'message' => $e->getMessage(),
                'status' => 'error',
            ], 500);
        }
    }

    public function userRentals()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized. Token invalid or not provided.'], 401);
            }

            $rentals = Rental::where('user_id', $user->id)->with('car.owner', 'user')->get();

            return response()->json([
                'message' => '',
                'data' => $rentals,
                'status' => 'success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user rentals.',
                'message' => $e->getMessage(),
                'status' => 'error',
            ], 500);
        }
    }

    public function ownerRentals()
    {
        try {
            $owner_id = JWTAuth::parseToken()->authenticate()->id;

            if (!$owner_id) {
                return response()->json(['error' => 'Unauthorized. Token invalid or not provided.'], 401);
            }

            $rentals = Rental::whereHas('car', function ($query) use ($owner_id) {
                $query->where('owner_id', $owner_id);
            })->with(['car.owner', 'user'])->get();

            return response()->json([
                'message' => '',
                'data' => $rentals,
                'status' => 'success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch owner rentals.',
                'message' => $e->getMessage(),
                'status' => 'error',
            ], 500);
        }
    }

    public function returnCar(Request $request)
    {
        try {
            $request->validate([
                'rental_id' => 'required|exists:rentals,id',
                'return_date' => 'required|date|after_or_equal:today',
            ]);

            $rental = Rental::find($request->rental_id);
            $daysRented = Carbon::parse($rental->start_date)->diffInDays($rental->end_date);
            $totalCost = $daysRented * $rental->car->rental_rate;

            $carReturn = new CarReturn();
            $carReturn->rental_id = $rental->id;
            $carReturn->return_date = $request->return_date;
            $carReturn->total_cost = $totalCost;
            $carReturn->save();

            $rental->car->available = true;
            $rental->car->save();

            return response()->json([
                'message' => 'Car returned successfully!',
                'total_cost' => $totalCost,
                'status' => 'success',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'status' => 'error',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to return car.',
                'message' => $e->getMessage(),
                'status' => 'error',
            ], 500);
        }
    }
}
