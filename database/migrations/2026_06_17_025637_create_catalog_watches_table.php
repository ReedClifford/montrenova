<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_watches', function (Blueprint $table) {
            $table->id();
            $table->string('brand')->default('Seiko');
            $table->string('model_name');
            $table->string('reference_number')->nullable()->index();
            $table->string('category')->nullable()->index();
            $table->string('image_path')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_watches');
    }
};