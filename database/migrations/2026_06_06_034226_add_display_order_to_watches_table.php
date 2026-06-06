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
            $table->unsignedInteger('display_order')
                ->default(0)
                ->after('is_visible')
                ->index();
        });

        DB::table('watches')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->values()
            ->each(function ($watch, $index) {
                DB::table('watches')
                    ->where('id', $watch->id)
                    ->update([
                        'display_order' => $index + 1,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            $table->dropColumn('display_order');
        });
    }
};