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
            'serial_number' => ['nullable', 'string', 'max:255'],
        ]);

        $buyerName = trim($validated['buyer_name']);
        $serialNumber = trim($validated['serial_number'] ?? '');

        $watch = Watch::query()
            ->where('status', 'sold')
            ->whereNotNull('date_sold')
            ->whereRaw('LOWER(TRIM(buyer_name)) = ?', [strtolower($buyerName)])
            ->when($serialNumber !== '', function ($query) use ($serialNumber) {
                $query->whereRaw('LOWER(TRIM(serial_number)) = ?', [
                    strtolower($serialNumber),
                ]);
            })
            ->latest('date_sold')
            ->first();

        if (! $watch) {
            return Inertia::render('Public/WarrantyCheck', [
                'result' => null,
                'searched' => true,
            ]);
        }

        $dateSold = Carbon::parse($watch->date_sold)->startOfDay();
        $warrantyEnd = $dateSold->copy()->addYear();
        $daysLeft = now()->startOfDay()->diffInDays($warrantyEnd, false);

        if ($daysLeft < 0) {
            $warrantyStatus = 'expired';
        } elseif ($daysLeft <= 30) {
            $warrantyStatus = 'expiring_soon';
        } else {
            $warrantyStatus = 'active';
        }

        return Inertia::render('Public/WarrantyCheck', [
            'searched' => true,
            'result' => [
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
            ],
        ]);
    }
}