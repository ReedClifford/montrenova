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
            'searched' => false,
        ]);
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:255'],
        ]);

        $buyerName = trim($validated['buyer_name']);
        $serialNumber = trim($validated['serial_number']);

        $watch = Watch::query()
            ->where('status', 'sold')
            ->whereNotNull('date_sold')
            ->where('serial_number', $serialNumber)
            ->whereRaw('LOWER(TRIM(buyer_name)) = ?', [strtolower($buyerName)])
            ->first();

        if (! $watch) {
            return Inertia::render('Public/WarrantyCheck', [
                'result' => null,
                'searched' => true,
            ]);
        }

        $dateSold = Carbon::parse($watch->date_sold);
        $warrantyEnd = $dateSold->copy()->addYear();
        $daysLeft = now()->startOfDay()->diffInDays($warrantyEnd->copy()->startOfDay(), false);

        if ($daysLeft < 0) {
            $status = 'expired';
        } elseif ($daysLeft <= 30) {
            $status = 'expiring_soon';
        } else {
            $status = 'active';
        }

        return Inertia::render('Public/WarrantyCheck', [
            'searched' => true,
            'result' => [
                'buyer_name' => $watch->buyer_name,
                'brand' => $watch->brand,
                'model_name' => $watch->model_name,
                'reference_number' => $watch->reference_number,
                'serial_number' => $watch->serial_number,
                'date_sold' => $dateSold->format('Y-m-d'),
                'warranty_start_date' => $dateSold->format('Y-m-d'),
                'warranty_end_date' => $warrantyEnd->format('Y-m-d'),
                'days_left' => $daysLeft,
                'status' => $status,
            ],
        ]);
    }
}