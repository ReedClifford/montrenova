<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preorder_listings', function (Blueprint $table) {
            $table->foreignId('source_watch_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('preorder_listings')->whereNull('source_watch_id')->exists()) {
            throw new RuntimeException('Cannot require a sold source while manual preorder listings exist.');
        }

        Schema::table('preorder_listings', function (Blueprint $table) {
            $table->foreignId('source_watch_id')->nullable(false)->change();
        });
    }
};
