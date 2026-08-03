<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    if (! Schema::hasColumn('catalog_watches', 'brand')) {
        Schema::table('catalog_watches', function (Blueprint $table) {
            $table
                ->string('brand')
                ->default('Seiko')
                ->after('id');
        });
    }

    if (! Schema::hasColumn('catalog_watches', 'model_name')) {
        Schema::table('catalog_watches', function (Blueprint $table) {
            $table
                ->string('model_name')
                ->after('brand');
        });
    }

    if (! Schema::hasColumn('catalog_watches', 'reference_number')) {
        Schema::table('catalog_watches', function (Blueprint $table) {
            $table
                ->string('reference_number')
                ->nullable()
                ->after('model_name');
        });
    }

    if (! Schema::hasColumn('catalog_watches', 'category')) {
        Schema::table('catalog_watches', function (Blueprint $table) {
            $table
                ->string('category')
                ->nullable()
                ->after('reference_number');
        });
    }

    if (! Schema::hasColumn('catalog_watches', 'image_path')) {
        Schema::table('catalog_watches', function (Blueprint $table) {
            $table
                ->string('image_path')
                ->nullable()
                ->after('category');
        });
    }

    if (! Schema::hasColumn('catalog_watches', 'is_visible')) {
        Schema::table('catalog_watches', function (Blueprint $table) {
            $table
                ->boolean('is_visible')
                ->default(true)
                ->after('image_path');
        });
    }

    if (! Schema::hasColumn('catalog_watches', 'sort_order')) {
        Schema::table('catalog_watches', function (Blueprint $table) {
            $table
                ->unsignedInteger('sort_order')
                ->default(0)
                ->after('is_visible');
        });
    }
}

   public function down(): void
{
    $columns = [
        'brand',
        'model_name',
        'reference_number',
        'category',
        'image_path',
        'is_visible',
        'sort_order',
    ];

    $existingColumns = array_values(
        array_filter(
            $columns,
            fn (string $column): bool =>
                Schema::hasColumn(
                    'catalog_watches',
                    $column
                )
        )
    );

    if (empty($existingColumns)) {
        return;
    }

    Schema::table(
        'catalog_watches',
        function (Blueprint $table) use (
            $existingColumns
        ) {
            $table->dropColumn($existingColumns);
        }
    );
}
};