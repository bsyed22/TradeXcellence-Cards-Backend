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
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('order_id')->unique()->nullable();
            $table->string('transaction_fee_payer')->nullable()->after('status');
            $table->string('currency')->nullable()->after('transaction_fee_payer');
            $table->string('charge_type')->nullable()->after('currency');
            $table->string('transaction_fee')->nullable()->after('charge_type');
            $table->longText('transaction_details')->nullable()->nullable()->after('transaction_fee');
            $table->string('merchant')->nullable()->after('amount');
            $table->string('comments')->nullable()->after('merchant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');

    }
};
