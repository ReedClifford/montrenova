<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preorder_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preorder_id')->constrained()->restrictOnDelete();
            $table->foreignId('preorder_listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('fulfilled_watch_id')->nullable()->unique()->constrained('watches')->restrictOnDelete();
            $table->decimal('price', 12, 2);
        });
    }

    public function down(): void
    {
        if (DB::table('preorder_items')->exists()) {
            throw new RuntimeException('Cannot roll back while multi-watch preorders exist.');
        }

        Schema::dropIfExists('preorder_items');
    }
};
