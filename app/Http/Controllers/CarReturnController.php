<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CarReturn;
use App\Models\Rental;
use Illuminate\Support\Facades\DB;

class CarReturnController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'rental_id' => 'required|exists:rentals,id',
            'return_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $rental = Rental::findOrFail($request->rental_id);

            $startDate = new \DateTime($rental->start_date);
            $returnDate = new \DateTime($request->return_date);
            $daysRented = $startDate->diff($returnDate)->days;

            $totalCost = $daysRented * $rental->car->rental_rate;

            $carReturn = CarReturn::create([
                'rental_id' => $rental->id,
                'return_date' => $request->return_date,
                'days_rented' => $daysRented,
                'total_cost' => $totalCost,
            ]);

            $rental->update(['status' => 'completed']);

            DB::commit();

            return response()->json([
                'message' => 'Car returned successfully.',
                'data' => $carReturn,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'An error occurred while processing the return.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
