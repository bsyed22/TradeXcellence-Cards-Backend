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
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('approved_by')->nullable(); // Admin who processed it
            $table->string('payout_batch_id')->nullable()->unique();
            $table->string('transaction_fee_payer')->nullable();
            $table->string('currency')->nullable();
            $table->string('charge_type')->nullable();
            $table->decimal('transaction_fee', 13, 2)->nullable();
            $table->longText('transaction_details')->nullable();
            $table->string('merchant')->nullable();
            $table->json('request_data')->nullable(); // Store dynamic user-provided data for admin
            $table->string('comments')->nullable(); // Store dynamic user-provided data for admin

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');

    }
};
