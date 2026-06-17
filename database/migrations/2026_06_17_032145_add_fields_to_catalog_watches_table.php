<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_watches', function (Blueprint $table) {
            $table->string('brand')->default('Seiko')->after('id');
            $table->string('model_name')->after('brand');
            $table->string('reference_number')->nullable()->after('model_name')->index();
            $table->string('category')->nullable()->after('reference_number')->index();
            $table->string('image_path')->nullable()->after('category');
            $table->boolean('is_visible')->default(true)->after('image_path')->index();
            $table->unsignedInteger('sort_order')->default(0)->after('is_visible')->index();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_watches', function (Blueprint $table) {
            $table->dropIndex(['reference_number']);
            $table->dropIndex(['category']);
            $table->dropIndex(['is_visible']);
            $table->dropIndex(['sort_order']);

            $table->dropColumn([
                'brand',
                'model_name',
                'reference_number',
                'category',
                'image_path',
                'is_visible',
                'sort_order',
            ]);
        });
    }
};