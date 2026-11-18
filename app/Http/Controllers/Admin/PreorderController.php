<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Preorder;
use App\Models\PreorderEntry;
use App\Models\PreorderListing;
use App\Models\Watch;
use App\Services\PreorderAccounting;
use App\Services\PreorderFollowUps;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            'queue' => ['nullable', 'in:'.implode(',', array_keys(PreorderFollowUps::LABELS))],
        ]);
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);
        $queue = (string) $request->query('queue', '');
        $orders = PreorderFollowUps::apply(Preorder::with(['listing', 'entries', 'items.listing']), $queue)
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('customer_name', 'like', "%{$search}%")->orWhere('contact', 'like', "%{$search}%")));

        return Inertia::render('Admin/Preorders/Index', [
            'sourceWatch' => $request->query('source_watch_id') ? Watch::where('status', 'sold')
                ->find($request->integer('source_watch_id'), ['id', 'brand', 'model_name', 'reference_number', 'selling_price']) : null,
            'listings' => PreorderListing::withCount('orders')->latest()->get(),
            'orders' => $orders->latest()->paginate(15)->withQueryString(),
            'followUps' => collect(PreorderFollowUps::LABELS)->map(fn ($label, $key) => [
                'key' => $key, 'label' => $label,
                'count' => PreorderFollowUps::apply(Preorder::query(), $key)->count(),
            ])->values(),
            'soldWatches' => Watch::where('status', 'sold')->when($request->query('source_search'), fn ($q, $term) => $q
                ->where(fn ($q) => $q->where('model_name', 'like', '%'.mb_substr($term, 0, 100).'%')
                    ->orWhere('reference_number', 'like', '%'.mb_substr($term, 0, 100).'%')))
                ->whereNotIn('id', PreorderListing::whereNotNull('source_watch_id')->select('source_watch_id'))->latest()->limit(50)
                ->get(['id', 'brand', 'model_name', 'reference_number', 'selling_price']),
            'filters' => ['search' => $search, 'source_search' => $request->query('source_search', ''), 'queue' => $queue],
            'summary' => PreorderAccounting::summary(),
        ]);
    }

    public function storeListing(Request $request)
    {
        $data = $request->validate([
            'source_watch_id' => ['nullable', 'integer', 'exists:watches,id', 'unique:preorder_listings,source_watch_id'],
            'brand' => ['required_without:source_watch_id', 'nullable', 'string', 'max:255'],
            'model_name' => ['required_without:source_watch_id', 'nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'estimated_arrival' => ['nullable', 'string', 'max:255'],
        ]);
        $copied = [];
        try {
            DB::transaction(function () use ($data, &$copied) {
                $listing = Arr::only($data, ['source_watch_id', 'price', 'estimated_arrival']);
                if (empty($data['source_watch_id'])) {
                    $snapshot = Arr::only($data, ['brand', 'model_name', 'reference_number', 'description']);
                    $snapshot['images'] = [];
                    $snapshot['sections'] = [];
                    foreach ($data['photos'] ?? [] as $photo) {
                        $path = $photo->store('preorders', 'public');
                        $this->check((bool) $path, 'photos', 'Could not save the watch photo.');
                        $copied[] = $path;
                        $snapshot['images'][] = ['path' => $path];
                    }
                    PreorderListing::create($listing + ['watch_snapshot' => $snapshot]);

                    return;
                }

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
                PreorderListing::create($listing + ['watch_snapshot' => $snapshot]);
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
            'preorder_listing_id' => ['required_without:items', Rule::prohibitedIf($request->has('items')), 'exists:preorder_listings,id'],
            'items' => ['sometimes', 'required', 'array', 'list', 'min:1', 'max:20'],
            'items.*' => ['array:preorder_listing_id,brand,model_name,reference_number,price'],
            'items.*.preorder_listing_id' => ['nullable', 'integer', 'exists:preorder_listings,id'],
            'items.*.brand' => ['required_without:items.*.preorder_listing_id', 'nullable', 'string', 'max:255'],
            'items.*.model_name' => ['required_without:items.*.preorder_listing_id', 'nullable', 'string', 'max:255'],
            'items.*.reference_number' => ['nullable', 'string', 'max:255'],
            'items.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'customer_name' => ['required', 'string', 'max:255'], 'contact' => ['required', 'string', 'max:255'],
            'total_price' => ['required_without:items', Rule::prohibitedIf($request->has('items')), 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            'deposit_percentage' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:100'],
            'estimated_arrival' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $items = Arr::pull($data, 'items', []);
        if ($items) {
            $total = array_sum(array_map(fn ($item) => (int) round($item['price'] * 100), $items));
            $this->check($total <= 999999999999, 'items', 'The combined price exceeds the supported limit.');
            $data['total_price'] = $total / 100;
        }
        $order = DB::transaction(function () use ($data, $items) {
            $items = array_map(function (array $item): array {
                $listingId = $item['preorder_listing_id'] ?? null;
                if ($listingId === null) {
                    $listingId = PreorderListing::create([
                        'watch_snapshot' => Arr::only($item, ['brand', 'model_name', 'reference_number']),
                        'price' => $item['price'],
                        'is_visible' => false,
                    ])->id;
                }

                return ['preorder_listing_id' => $listingId, 'price' => $item['price']];
            }, $items);
            if ($items) {
                $data['preorder_listing_id'] = $items[0]['preorder_listing_id'];
            }

            $order = Preorder::create($data);
            if (count($items) > 1) {
                $order->items()->createMany($items);
            }

            return $order;
        });

        return to_route('admin.preorders.show', $order)->with('success', 'Customer preorder created.');
    }

    public function show(Preorder $preorder)
    {
        return Inertia::render('Admin/Preorders/Show', [
            'order' => $preorder->load(['listing', 'entries', 'items.listing']),
            'reversals' => DB::table('preorder_fulfillment_reversals as reversals')
                ->leftJoin('users', 'users.id', '=', 'reversals.recorded_by')
                ->where('reversals.preorder_id', $preorder->id)->latest('reversals.id')
                ->get(['reversals.id', 'reversals.watch_id', 'reversals.reason', 'reversals.created_at', 'users.name as recorded_by']),
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

    public function updateEta(Request $request, Preorder $preorder)
    {
        $data = $request->validate([
            'estimated_arrival' => ['present', 'nullable', 'date_format:Y-m-d'],
        ]);

        DB::transaction(function () use ($preorder, $data) {
            $order = Preorder::lockForUpdate()->findOrFail($preorder->id);
            $this->check(! in_array($order->status, ['fulfilled', 'cancelled'], true), 'estimated_arrival', 'Only active preorders can have their estimated arrival changed.');
            $order->update($data);
        });

        return back()->with('success', 'Estimated arrival updated.');
    }

    public function fulfill(Request $request, Preorder $preorder)
    {
        $copied = [];
        try {
            DB::transaction(function () use ($request, $preorder, &$copied) {
                $order = Preorder::lockForUpdate()->findOrFail($preorder->id)->load(['entries', 'listing', 'items.listing']);
                $this->check($order->status === 'arrived', 'serial_number', 'Confirm all watches are ready for handover first.');
                $this->check($order->balance <= 0, 'serial_number', 'The remaining balance must be fully paid.');
                $this->check($order->cost > 0, 'serial_number', 'Record the actual supplier and direct costs first.');

                if ($order->items->isEmpty()) {
                    $data = $request->validate(['serial_number' => ['required', 'string', 'max:255']]);
                    $watch = $this->recordSale($order, $order->listing, $order->total_price, $order->cost, $data['serial_number'], $copied);
                } else {
                    $data = $request->validate([
                        'units' => ['required', 'array', 'list', 'size:'.$order->items->count()],
                        'units.*.serial_number' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
                        'units.*.cost' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
                    ]);
                    $cost = array_sum(array_map(fn ($unit) => (int) round($unit['cost'] * 100), $data['units']));
                    $this->check($cost === $order->cents('cost'), 'units', 'The costs assigned to the watches must add up to the recorded direct costs.');
                    $watch = null;
                    foreach ($order->items as $index => $item) {
                        $unit = $data['units'][$index];
                        $sale = $this->recordSale($order, $item->listing, $item->price, $unit['cost'], $unit['serial_number'], $copied);
                        $item->update(['fulfilled_watch_id' => $sale->id]);
                        $watch ??= $sale;
                    }
                }

                $order->update(['status' => 'fulfilled', 'fulfilled_watch_id' => $watch->id, 'fulfilled_at' => now()]);
            });
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($copied);
            throw $error;
        }

        return back()->with('success', 'Preorder fulfilled. Sales, profit and warranties are now recorded.');
    }

    private function recordSale(Preorder $order, PreorderListing $listing, string $price, float $cost, string $serial, array &$copied): Watch
    {
        $snapshot = $listing->watch_snapshot;
        unset($snapshot['images'], $snapshot['sections'], $snapshot['primary_image_url']);
        $watch = Watch::create($snapshot + [
            'slug' => 'preorder-'.$order->id.'-'.Str::uuid(), 'status' => 'sold', 'is_visible' => false,
            'capital_price' => $cost, 'selling_price' => $price, 'sold_price' => $price,
            'date_acquired' => today(), 'date_sold' => today(), 'buyer_name' => $order->customer_name,
            'serial_number' => $serial,
        ]);
        // Keep investor date-cutoff attribution consistent with the original order.
        $watch->created_at = $order->created_at;
        $watch->save();
        // Independent files: deleting a fulfilled stock must not remove listing photos.
        foreach ($listing->watch_snapshot['images'] ?? [] as $index => $image) {
            $path = 'watches/'.$watch->id.'/'.basename($image['path']);
            if (Storage::disk('public')->copy($image['path'], $path)) {
                $copied[] = $path;
                $watch->images()->create([
                    'image_path' => $path, 'hd_path' => $path, 'thumbnail_path' => $path,
                    'sort_order' => $index + 1, 'is_primary' => $index === 0,
                ]);
            }
        }
        foreach ($listing->watch_snapshot['sections'] ?? [] as $index => $section) {
            $watch->sections()->create($section + ['sort_order' => $index + 1]);
        }
        return $watch;
    }

    public function undoFulfillment(Request $request, Preorder $preorder)
    {
        $data = $request->validate([
            'watch_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $watchIds = DB::transaction(function () use ($request, $preorder, $data) {
            $order = Preorder::lockForUpdate()->findOrFail($preorder->id)->load('items');
            $this->check($order->status === 'fulfilled' && (int) $order->fulfilled_watch_id === (int) $data['watch_id'],
                'reason', 'This fulfillment has changed. Refresh the order before trying again.');

            $ids = $order->items->isEmpty() ? [$order->fulfilled_watch_id] : $order->items->pluck('fulfilled_watch_id')->all();
            $watches = Watch::lockForUpdate()->findMany($ids);
            $this->check($watches->count() === count($ids) && $watches->every(fn ($watch) => $watch->status === 'sold'),
                'reason', 'The linked sales have changed. Refresh the order before trying again.');

            foreach ($watches as $watch) {
                DB::table('preorder_fulfillment_reversals')->insert([
                    'preorder_id' => $order->id,
                    'recorded_by' => $request->user()->id,
                    'watch_id' => $watch->id,
                    'reason' => $data['reason'],
                    'sale_snapshot' => json_encode($watch->only(['brand', 'model_name', 'serial_number', 'date_sold', 'sold_price', 'capital_price'])
                        + ['fulfilled_at' => $order->fulfilled_at], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                ]);
            }

            $order->update(['status' => 'arrived', 'fulfilled_watch_id' => null, 'fulfilled_at' => null]);
            $order->items()->update(['fulfilled_watch_id' => null]);
            PreorderListing::whereIn('source_watch_id', $ids)->update(['source_watch_id' => null]);
            foreach ($watches as $watch) {
                $watch->delete();
            }

            return $ids;
        });

        foreach ($watchIds as $id) {
            rescue(fn () => Storage::disk('public')->deleteDirectory('watches/'.$id));
        }

        return back()->with('success', 'Fulfillment reversed. The preorder is back to Arrived.');
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
