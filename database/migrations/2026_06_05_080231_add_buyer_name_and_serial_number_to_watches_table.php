<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            if (! Schema::hasColumn('watches', 'serial_number')) {
                $table->string('serial_number')->nullable()->after('reference_number');
            }

            if (! Schema::hasColumn('watches', 'buyer_name')) {
                $table->string('buyer_name')->nullable()->after('date_sold');
            }
        });
    }

    public function down(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            if (Schema::hasColumn('watches', 'buyer_name')) {
                $table->dropColumn('buyer_name');
            }

            if (Schema::hasColumn('watches', 'serial_number')) {
                $table->dropColumn('serial_number');
            }
        });
    }
};