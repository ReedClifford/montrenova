<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_sections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('watch_id')
                ->constrained('watches')
                ->cascadeOnDelete();

            $table->string('title');
            $table->longText('content')->nullable();
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_sections');
    }
};