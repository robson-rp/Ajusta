<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AJUSTA subscriptions: plans, one subscription per account owner, and the
 * charges sent to payment gateways. Accounts without a subscription (existing
 * installs, companies created by the platform admin) are not restricted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('price_monthly');
            $table->json('features')->nullable();
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans');
            $table->string('status')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->json('notices')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_charges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('months');
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('AOA');
            $table->string('gateway');
            $table->string('method');
            $table->string('status')->index();
            $table->string('merchant_transaction_id', 15)->unique();
            $table->string('provider_id')->nullable()->index();
            $table->boolean('provider_successful')->nullable();
            $table->integer('provider_code')->nullable();
            $table->text('provider_message')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('entity_number')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('webhook_events')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach (config('billing.plans', []) as $plan) {
            DB::table('plans')->insert([
                'code' => $plan['code'],
                'name' => $plan['name'],
                'price_monthly' => $plan['price_monthly'],
                'features' => json_encode($plan['features']),
                'is_public' => true,
                'sort' => $plan['sort'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_charges');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
