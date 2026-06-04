<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('watches', 'sold_price')) {
            Schema::table('watches', function (Blueprint $table) {
                $table->decimal('sold_price', 12, 2)
                    ->nullable()
                    ->after('discounted_price');
            });
        }

        if (!Schema::hasColumn('watches', 'date_sold')) {
            Schema::table('watches', function (Blueprint $table) {
                $table->date('date_sold')
                    ->nullable()
                    ->after('date_acquired');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('watches', 'sold_price')) {
            Schema::table('watches', function (Blueprint $table) {
                $table->dropColumn('sold_price');
            });
        }

        if (Schema::hasColumn('watches', 'date_sold')) {
            Schema::table('watches', function (Blueprint $table) {
                $table->dropColumn('date_sold');
            });
        }
    }
};