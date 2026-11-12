<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watches', fn (Blueprint $table) => $table->unsignedTinyInteger('warranty_years')->default(1));
    }

    public function down(): void
    {
        Schema::table('watches', fn (Blueprint $table) => $table->dropColumn('warranty_years'));
    }
};
