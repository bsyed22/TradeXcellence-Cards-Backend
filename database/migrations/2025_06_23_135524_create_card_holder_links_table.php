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
        Schema::create('card_holder_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('card_holder_id')->nullable();
            $table->boolean('fee_paid')->default(false);
            $table->string('card_number');
            $table->string('type');
            $table->string('card_holder_name');
            $table->string('email');
            $table->string('alias');
            $table->string('status')->default('pending'); // e.g., pending, active, rejected
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();


            // Optional: Add foreign keys if desired
            // $table->foreign('card_id')->references('id')->on('cards')->onDelete('cascade');
            // $table->foreign('card_holder_id')->references('id')->on('card_holders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_holder_links');
    }
};
