<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Online bank payments through the payment gateway (PayMongo checkout).
     *
     *  - online_payments: one row per checkout the client starts. It only becomes money in the books when
     *    the gateway confirms it server-side (signed webhook, or the checkout session fetched with the
     *    secret key) — never from the browser redirect. payment_transaction_id is unique, so a confirmed
     *    checkout is recorded as exactly one PaymentTransaction however many times it is confirmed.
     *  - payment_webhook_events: every webhook the gateway sends, kept for audit and reconciliation; the
     *    provider's event id is unique so a repeated delivery is recognised and ignored.
     */
    public function up(): void
    {
        Schema::create('online_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('quotation_batch_id')->nullable()->index();
            $table->string('payment_stage', 40);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('PHP');
            $table->string('provider', 30)->default('paymongo');
            $table->string('checkout_session_id')->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->string('provider_payment_id')->nullable()->unique();
            $table->string('payment_method', 40)->nullable();
            // pending → paid | failed | cancelled | expired
            $table->string('status', 20)->default('pending');
            $table->string('reference', 60)->unique();   // our own reference shown to the client and sent to the gateway
            $table->foreignId('payment_transaction_id')->nullable()->unique()->constrained('payment_transactions')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'payment_stage', 'status']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->default('paymongo');
            $table->string('event_id')->nullable()->unique();
            $table->string('event_type', 80)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->string('checkout_session_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->string('result', 120)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('online_payments');
    }
};
