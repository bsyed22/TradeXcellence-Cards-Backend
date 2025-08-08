<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 16, 2);
            $table->decimal('fee', 16, 2)->nullable();
            $table->bigInteger('card_id')->nullable();
            $table->string('proof_image'); // Store path to uploaded image
            $table->string('txn_hash');
            $table->string('transaction_id')->nullable();
            $table->longText('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 16, 2);
            $table->bigInteger('card_id');
            $table->string('wallet_address');
            $table->string('blockchain');
            $table->string('txn_hash')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->longText('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('deposits');
    }
};
