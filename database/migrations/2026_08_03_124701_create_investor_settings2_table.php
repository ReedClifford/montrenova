<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'investor_settings2',
            function (Blueprint $table) {
                $table->id();

                $table->string(
                    'setting_key'
                )->unique();

                $table->decimal(
                    'capital_amount',
                    15,
                    2
                )->default(0);

                $table->decimal(
                    'investor_profit_percentage',
                    8,
                    4
                )->default(0);

                $table->decimal(
                    'management_fee_percentage',
                    8,
                    4
                )->default(0);

                $table->decimal(
                    'brand_cut_percentage',
                    8,
                    4
                )->default(50);

                $table->date(
                    'investment_start_date'
                )->nullable();

                $table->foreignId(
                    'updated_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'investor_settings2'
        );
    }
};
