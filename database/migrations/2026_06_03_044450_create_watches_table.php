<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watches', function (Blueprint $table) {
            $table->id();

           
            $table->string('brand')->nullable();
            $table->string('model_name');
            $table->string('reference_number')->nullable();
            $table->string('slug')->unique();

            $table->string('condition')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();

            $table->string('movement')->nullable();
            $table->string('case_size')->nullable();
            $table->string('case_material')->nullable();
            $table->string('dial_color')->nullable();
            $table->string('crystal')->nullable();
            $table->string('bracelet_or_strap')->nullable();
            $table->string('water_resistance')->nullable();
            $table->string('box_papers')->nullable();
            $table->string('warranty_type')->nullable();

            $table->decimal('capital_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('discounted_price', 12, 2)->nullable();

            $table->enum('status', [
                'draft',
                'available',
                'reserved',
                'sold',
                'hidden',
            ])->default('draft');

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('display_price')->default(true);
            $table->boolean('allow_inquiry')->default(true);

            $table->date('date_acquired')->nullable();
            $table->date('date_sold')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watches');
    }
};