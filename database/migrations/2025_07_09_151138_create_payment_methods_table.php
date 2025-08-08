<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED primary key
            $table->string('name', 191);
            $table->string('image', 191)->nullable();
            $table->decimal('minimum_amount', 11, 2)->default(0);
            $table->decimal('maximum_amount', 11, 2)->default(0);
            $table->enum('deposit_fee_payer', ['merchant', 'user'])->default('merchant');
            $table->enum('withdraw_fee_payer', ['merchant', 'user'])->default('merchant');
            $table->enum('withdraw_charge_type', ['fixed', 'percentage'])->default('fixed');
            $table->enum('deposit_charge_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('deposit_fixed_charge', 11, 2)->default(0);
            $table->decimal('deposit_percent_charge', 11, 2)->default(0);
            $table->decimal('withdraw_fixed_charge', 11, 2)->default(0);
            $table->decimal('withdraw_percent_charge', 11, 2)->default(0);
            $table->text('duration')->nullable();
            $table->json('convert_rate')->nullable(); // Conversion rates mapped to currency lists
            $table->boolean('is_automatic')->default(false);
            $table->decimal('amount_greater_than_equal', 11, 2)->nullable(); // Amount greater than or equal to
            $table->decimal('amount_less_than_equal', 11, 2)->nullable(); // Amount less than or equal to
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
