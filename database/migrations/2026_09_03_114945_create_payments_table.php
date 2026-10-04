<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stripe_session_id')->unique();
            $table->string('stripe_payment_intent')->nullable();
            $table->unsignedInteger('amount_cents')->default(500);
            $table->string('currency')->default('usd');
            $table->string('status')->default('created');
            $table->foreignUuid('postcard_id')->nullable()->constrained('postcards')->nullOnDelete();
            $table->json('draft');
            $table->string('creator_slug')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
