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
        Schema::create('referral_programs', function (Blueprint $table) {
            $table->id();
            $table->string('program_name');
            $table->enum('program_type', ['public', 'private']);
            $table->enum('bonus_type', ['percentage', 'fixed']);
            $table->decimal('bonus_amount', 10, 2)->nullable(); // % or fixed
            $table->enum('bonus_validity_type', ['expires_on', 'max_claims']);
            $table->date('expires_at')->nullable();
            $table->unsignedInteger('max_claims')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_programs');
    }
};
