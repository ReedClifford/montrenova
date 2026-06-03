<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('watches', 'stock_code')) {
            Schema::table('watches', function (Blueprint $table) {
                $table->dropUnique(['stock_code']);
                $table->dropColumn('stock_code');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('watches', 'stock_code')) {
            Schema::table('watches', function (Blueprint $table) {
                $table->string('stock_code')->nullable()->unique()->after('id');
            });
        }
    }
};