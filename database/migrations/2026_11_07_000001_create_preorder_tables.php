<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preorder_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_watch_id')->unique()->constrained('watches')->restrictOnDelete();
            $table->json('watch_snapshot');
            $table->decimal('price', 12, 2);
            $table->string('estimated_arrival')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
        Schema::create('preorders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preorder_listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('fulfilled_watch_id')->nullable()->unique()->constrained('watches')->restrictOnDelete();
            $table->string('customer_name');
            $table->string('contact')->nullable();
            $table->decimal('total_price', 12, 2);
            $table->decimal('deposit_percentage', 5, 2)->default(20);
            $table->string('status')->default('awaiting_deposit')->index();
            $table->date('estimated_arrival')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('preorder_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preorder_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->uuid('request_key')->unique();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->string('method');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('proof_path')->nullable();
            $table->timestamps();
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('preorder_id')->nullable()->unique()->constrained('preorders')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', fn (Blueprint $table) => $table->dropConstrainedForeignId('preorder_id'));
        Schema::dropIfExists('preorder_entries');
        Schema::dropIfExists('preorders');
        Schema::dropIfExists('preorder_listings');
    }
};
