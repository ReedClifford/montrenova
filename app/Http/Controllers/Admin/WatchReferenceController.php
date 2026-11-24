<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Watch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchReferenceController extends Controller
{
    private const FIELDS = [
        'brand', 'model_name', 'movement', 'case_size', 'case_material',
        'dial_color', 'crystal', 'bracelet_or_strap', 'water_resistance',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $input = $request->validate([
            'reference' => ['required', 'string', 'max:255', 'regex:/\A[A-Za-z0-9][A-Za-z0-9 .\/_-]*\z/'],
        ]);
        $reference = $this->normalize($input['reference']);

        $catalog = json_decode(file_get_contents(database_path('data/tissot-watch-specs.json')), true, flags: JSON_THROW_ON_ERROR);
        $match = $catalog[$reference] ?? null;

        if ($match) {
            return response()->json([
                'details' => ['brand' => 'Tissot', ...$match['details']],
                'source' => 'Tissot specs table',
                'source_url' => "https://www.tissotwatches.com/en-us/{$reference}.html",
                'verification_status' => $match['verification_status'],
            ]);
        }

        $watch = Watch::query()
            ->whereRaw("UPPER(REPLACE(REPLACE(REPLACE(reference_number, '.', ''), '-', ''), ' ', '')) = ?", [$reference])
            ->where('brand', '<>', '')
            ->where('model_name', '<>', '')
            ->latest('id')
            ->first(self::FIELDS);

        if ($watch) {
            return response()->json([
                'details' => $watch->only(self::FIELDS),
                'source' => 'Existing inventory',
            ]);
        }

        return response()->json([
            'message' => 'No match in inventory or the Tissot specs table. Enter the specs manually.',
        ], 404);
    }

    private function normalize(string $reference): string
    {
        return strtoupper(str_replace(['.', '-', ' '], '', $reference));
    }
}
