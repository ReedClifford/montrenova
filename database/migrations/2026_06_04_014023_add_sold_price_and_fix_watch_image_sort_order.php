<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            if (!Schema::hasColumn('watches', 'sold_price')) {
                $table->decimal('sold_price', 12, 2)->nullable()->after('discounted_price');
            }

            if (!Schema::hasColumn('watches', 'date_sold')) {
                $table->date('date_sold')->nullable()->after('date_acquired');
            }
        });

        Schema::table('watch_images', function (Blueprint $table) {
            if (!Schema::hasColumn('watch_images', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_primary');
            }
        });

        $watchIds = DB::table('watch_images')
            ->select('watch_id')
            ->distinct()
            ->pluck('watch_id');

        foreach ($watchIds as $watchId) {
            $images = DB::table('watch_images')
                ->where('watch_id', $watchId)
                ->orderByDesc('is_primary')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            foreach ($images as $index => $image) {
                DB::table('watch_images')
                    ->where('id', $image->id)
                    ->update([
                        'sort_order' => $index + 1,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            if (Schema::hasColumn('watches', 'sold_price')) {
                $table->dropColumn('sold_price');
            }
        });
    }
};