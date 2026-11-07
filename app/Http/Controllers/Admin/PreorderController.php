<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Preorder;
use App\Models\PreorderEntry;
use App\Models\PreorderListing;
use App\Models\Watch;
use App\Services\PreorderAccounting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PreorderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'source_search' => ['nullable', 'string', 'max:100'],
            'source_watch_id' => ['nullable', 'integer'],
        ]);
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);

        return Inertia::render('Admin/Preorders/Index', [
            'sourceWatch' => $request->query('source_watch_id') ? Watch::where('status', 'sold')
                ->find($request->integer('source_watch_id'), ['id', 'brand', 'model_name', 'reference_number', 'selling_price']) : null,
            'listings' => PreorderListing::withCount('orders')->latest()->get(),
            'orders' => Preorder::with(['listing', 'entries'])->when($search, fn ($q) => $q
                ->where(fn ($q) => $q->where('customer_name', 'like', "%{$search}%")->orWhere('contact', 'like', "%{$search}%")))
                ->latest()->paginate(15)->withQueryString(),
            'soldWatches' => Watch::where('status', 'sold')->when($request->query('source_search'), fn ($q, $term) => $q
                ->where(fn ($q) => $q->where('model_name', 'like', '%'.mb_substr($term, 0, 100).'%')
                    ->orWhere('reference_number', 'like', '%'.mb_substr($term, 0, 100).'%')))
                ->whereNotIn('id', PreorderListing::select('source_watch_id'))->latest()->limit(50)
                ->get(['id', 'brand', 'model_name', 'reference_number', 'selling_price']),
            'filters' => ['search' => $search, 'source_search' => $request->query('source_search', '')],
            'summary' => PreorderAccounting::summary(),
        ]);
    }

    public function storeListing(Request $request)
    {
        $data = $request->validate([
            'source_watch_id' => ['required', 'integer', 'exists:watches,id', 'unique:preorder_listings,source_watch_id'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'estimated_arrival' => ['nullable', 'string', 'max:255'],
        ]);
        $copied = [];
        try {
            DB::transaction(function () use ($data, &$copied) {
                $watch = Watch::with(['images', 'sections'])->lockForUpdate()->findOrFail($data['source_watch_id']);
                $this->check($watch->status === 'sold', 'source_watch_id', 'Choose a sold watch.');
                $snapshot = $watch->only(['brand', 'model_name', 'reference_number', 'condition', 'category', 'description',
                    'movement', 'case_size', 'case_material', 'dial_color', 'crystal', 'bracelet_or_strap',
                    'water_resistance', 'box_papers', 'warranty_type']);
                $snapshot['images'] = [];
                foreach ($watch->images->sortByDesc('is_primary')->values() as $image) {
                    if (! Storage::disk('public')->exists($image->image_path)) {
                        continue;
                    }
                    $path = 'preorders/'.Str::uuid().'.'.pathinfo($image->image_path, PATHINFO_EXTENSION);
                    $this->check(Storage::disk('public')->copy($image->image_path, $path), 'source_watch_id', 'Could not copy watch photos.');
                    $copied[] = $path;
                    $snapshot['images'][] = ['path' => $path, 'image_url' => Storage::disk('public')->url($path)];
                }
                $snapshot['primary_image_url'] = $snapshot['images'][0]['image_url'] ?? null;
                $snapshot['sections'] = $watch->sections->map->only(['title', 'content'])->all();
                PreorderListing::create($data + ['watch_snapshot' => $snapshot]);
            });
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($copied);
            throw $error;
        }

        return back()->with('success', 'Preorder listing published to the Collection.');
    }

    public function updateListing(Request $request, PreorderListing $listing)
    {
        $listing->update($request->validate([
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'estimated_arrival' => ['nullable', 'string', 'max:255'], 'is_visible' => ['required', 'boolean'],
        ]));

        return back()->with('success', 'Listing updated. Existing order prices are unchanged.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'preorder_listing_id' => ['required', 'exists:preorder_listings,id'],
            'customer_name' => ['required', 'string', 'max:255'], 'contact' => ['required', 'string', 'max:255'],
            'total_price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'deposit_percentage' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:100'],
            'estimated_arrival' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $order = Preorder::create($data);

        return to_route('admin.preorders.show', $order)->with('success', 'Customer preorder created.');
    }

    public function show(Preorder $preorder)
    {
        return Inertia::render('Admin/Preorders/Show', [
            'order' => $preorder->load(['listing', 'entries']),
            'trackingUrl' => $preorder->tracking_token
                ? route('public.preorders.track', $preorder->tracking_token) : null,
        ]);
    }

    public function tracking(Preorder $preorder)
    {
        $preorder->update(['tracking_token' => Str::random(64)]);

        return back()->with('success', 'New private tracking link ready. Any previous link is now disabled.');
    }

    public function entry(Request $request, Preorder $preorder)
    {
        $data = $request->validate([
            'request_key' => ['required', 'uuid'], 'type' => ['required', 'in:payment,cost,refund'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'paid_at' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$preorder->created_at->toDateString()],
            'method' => ['required', 'string', 'max:100'], 'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'], 'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $proof = null;
        try {
            DB::transaction(function () use ($request, $preorder, $data, &$proof) {
                $order = Preorder::lockForUpdate()->findOrFail($preorder->id)->load('entries');
                $existing = PreorderEntry::where('request_key', $data['request_key'])->first();
                if ($existing) {
                    $this->check($existing->preorder_id === $order->id, 'amount', 'This payment request has already been used.');

                    return;
                }
                $this->check($order->status !== 'fulfilled', 'amount', 'Fulfilled orders are locked.');
                if ($data['type'] === 'refund') {
                    $this->check($order->status === 'cancelled', 'amount', 'Cancel the order before recording a refund.');
                    $this->check((int) round($data['amount'] * 100) <= (int) round($order->refundable * 100), 'amount', 'Refund exceeds the remaining customer deposit.');
                } else {
                    $this->check($order->status !== 'cancelled', 'amount', 'Cancelled orders only accept refunds.');
                    if ($data['type'] === 'cost') {
                        $this->check($order->cost + (float) $data['amount'] <= 9999999999.99, 'amount', 'Total supplier spending exceeds the supported limit.');
                    }
                    if ($data['type'] === 'payment') {
                        $this->check((int) round($data['amount'] * 100) <= (int) round($order->balance * 100), 'amount', 'Payment exceeds the remaining balance.');
                    }
                }
                if ($request->hasFile('proof')) {
                    $proof = $request->file('proof')->store('preorder-proofs', 'local');
                }
                unset($data['proof']);
                $order->entries()->create($data + ['recorded_by' => $request->user()->id, 'proof_path' => $proof]);
                $order->unsetRelation('entries');
                if ($order->status === 'awaiting_deposit' && $order->paid >= $order->required_deposit) {
                    $order->update(['status' => 'confirmed']);
                }
            });
        } catch (\Throwable $error) {
            if ($proof) {
                Storage::disk('local')->delete($proof);
            }
            throw $error;
        }

        return back()->with('success', 'Transaction recorded.');
    }

    public function update(Request $request, Preorder $preorder)
    {
        $data = $request->validate([
            'status' => ['required', 'in:ordered,arrived,cancelled'],
            'cancellation_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:5000'],
        ]);
        DB::transaction(function () use ($preorder, $data) {
            $order = Preorder::lockForUpdate()->findOrFail($preorder->id)->load('entries');
            $allowed = ['awaiting_deposit' => ['cancelled'], 'confirmed' => ['ordered', 'cancelled'],
                'ordered' => ['arrived', 'cancelled'], 'arrived' => ['cancelled']];
            $this->check(in_array($data['status'], $allowed[$order->status] ?? []), 'status', 'This status transition is not allowed.');
            if ($data['status'] === 'cancelled') {
                $data['cancelled_at'] = now();
                // Written-off supplier spending is an expense once cancellation is final.
                if ($order->cost > 0) {
                    (new Expense)->forceFill(['title' => 'Cancelled preorder #'.$order->id,
                        'category' => 'Preorder cancellation', 'amount' => $order->cost, 'spent_at' => today(),
                        'preorder_id' => $order->id, 'created_at' => $order->created_at,
                        'notes' => 'Supplier spending written off. '.$data['cancellation_reason']])->save();
                }
            }
            $order->update($data);
        });

        return back()->with('success', 'Order status updated.');
    }

    public function fulfill(Request $request, Preorder $preorder)
    {
        $data = $request->validate(['serial_number' => ['required', 'string', 'max:255']]);
        DB::transaction(function () use ($preorder, $data) {
            $order = Preorder::lockForUpdate()->findOrFail($preorder->id)->load(['entries', 'listing']);
            $this->check($order->status === 'arrived', 'serial_number', 'Mark the order as arrived before fulfillment.');
            $this->check($order->balance <= 0, 'serial_number', 'The remaining balance must be fully paid.');
            $this->check($order->cost > 0, 'serial_number', 'Record the actual supplier and direct costs first.');
            $snapshot = $order->listing->watch_snapshot;
            unset($snapshot['images'], $snapshot['sections'], $snapshot['primary_image_url']);
            $watch = Watch::create($snapshot + [
                'slug' => 'preorder-'.$order->id.'-'.Str::uuid(), 'status' => 'sold', 'is_visible' => false,
                'capital_price' => $order->cost, 'selling_price' => $order->total_price, 'sold_price' => $order->total_price,
                'date_acquired' => today(), 'date_sold' => today(), 'buyer_name' => $order->customer_name,
                'serial_number' => $data['serial_number'],
            ]);
            // Keep investor date-cutoff attribution consistent with the original order.
            $watch->created_at = $order->created_at;
            $watch->save();
            // Independent files: deleting a fulfilled stock must not remove listing photos.
            foreach ($order->listing->watch_snapshot['images'] ?? [] as $index => $image) {
                $path = 'watches/'.$watch->id.'/'.basename($image['path']);
                if (Storage::disk('public')->copy($image['path'], $path)) {
                    $watch->images()->create([
                        'image_path' => $path, 'hd_path' => $path, 'thumbnail_path' => $path,
                        'sort_order' => $index + 1, 'is_primary' => $index === 0,
                    ]);
                }
            }
            foreach ($order->listing->watch_snapshot['sections'] ?? [] as $index => $section) {
                $watch->sections()->create($section + ['sort_order' => $index + 1]);
            }
            $order->update(['status' => 'fulfilled', 'fulfilled_watch_id' => $watch->id, 'fulfilled_at' => now()]);
        });

        return back()->with('success', 'Preorder fulfilled. Sale, profit and warranty are now recorded.');
    }

    public function proof(PreorderEntry $entry)
    {
        abort_unless($entry->proof_path && Storage::disk('local')->exists($entry->proof_path), 404);

        return Storage::disk('local')->download($entry->proof_path);
    }

    private function check(bool $condition, string $field, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }
}
