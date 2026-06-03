<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            $table->string('reserved_customer_name')->nullable();
            $table->string('reserved_contact_number')->nullable();
            $table->date('reservation_date')->nullable();
            $table->date('reservation_deadline')->nullable();
            $table->text('reservation_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            $table->dropColumn([
                'reserved_customer_name',
                'reserved_contact_number',
                'reservation_date',
                'reservation_deadline',
                'reservation_notes',
            ]);
        });
    }
};