<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watches', fn (Blueprint $table) => $table->unsignedInteger('sale_cycle')->default(0));

        Schema::create('sale_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('watch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sale_cycle')->nullable();
            $table->unique(['watch_id', 'sale_cycle']);
            $table->foreignId('preorder_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('customer_token', 64)->nullable()->unique();
            $table->json('snapshot');
            $table->string('fingerprint', 64)->nullable();
            $table->unique(['preorder_id', 'fingerprint']);
            $table->timestamps();
        });

        Schema::create('preorder_fulfillment_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preorder_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('watch_id');
            $table->text('reason');
            $table->json('sale_snapshot');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preorder_fulfillment_reversals');
        Schema::dropIfExists('sale_invoices');
        Schema::table('watches', fn (Blueprint $table) => $table->dropColumn('sale_cycle'));
    }
};
