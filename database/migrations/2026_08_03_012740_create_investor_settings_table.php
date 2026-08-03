<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('capital_amount', 15, 2)
                ->default(0);

            $table->decimal('investor_profit_percentage', 5, 2)
                ->default(100);

            $table->decimal('management_fee_percentage', 5, 2)
                ->default(10);

            $table->decimal('brand_cut_percentage', 5, 2)
                ->default(40);

            $table->date('investment_start_date')
                ->default('2026-08-03');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_settings');
    }
};