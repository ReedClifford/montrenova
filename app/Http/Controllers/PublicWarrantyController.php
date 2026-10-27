<?php

namespace App\Http\Controllers;

use App\Models\Watch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicWarrantyController extends Controller
{
    public function index()
    {
        return Inertia::render('Public/WarrantyCheck', [
            'result' => null,
            'results' => [],
            'searched' => false,
        ]);
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
        ]);

        $buyerName = trim($validated['buyer_name']);
        $serialNumber = trim($validated['serial_number'] ?? '');

        $watches = Watch::query()
            ->where('status', 'sold')
            ->whereNotNull('date_sold')
            ->whereRaw('LOWER(TRIM(buyer_name)) = ?', [mb_strtolower($buyerName)])
            ->when($serialNumber !== '', function ($query) use ($serialNumber) {
                $query->whereRaw('LOWER(TRIM(serial_number)) = ?', [
                    mb_strtolower($serialNumber),
                ]);
            })
            ->orderByDesc('date_sold')
            ->orderByDesc('id')
            ->get();

        $today = Carbon::today();

        $results = $watches->map(function (Watch $watch) use ($today) {
            $dateSold = Carbon::parse($watch->date_sold)->startOfDay();
            $warrantyEnd = $dateSold->copy()->addYear();
            $daysLeft = (int) $today->diffInDays($warrantyEnd, false);

            $warrantyStatus = $daysLeft < 0
                ? 'expired'
                : ($daysLeft <= 30 ? 'expiring_soon' : 'active');

            return [
                'id' => $watch->id,
                'buyer_name' => $watch->buyer_name,
                'brand' => $watch->brand,
                'model_name' => $watch->model_name,
                'reference_number' => $watch->reference_number,
                'serial_number' => $watch->serial_number,
                'date_sold' => $dateSold->format('Y-m-d'),
                'warranty_start_date' => $dateSold->format('Y-m-d'),
                'warranty_end_date' => $warrantyEnd->format('Y-m-d'),
                'days_left' => $daysLeft,
                'status' => $warrantyStatus,
            ];
        })->values()->all();

        return Inertia::render('Public/WarrantyCheck', [
            'searched' => true,
            'results' => $results,
            // Backwards compatibility for any view still expecting one result.
            'result' => $results[0] ?? null,
        ]);
    }
}
