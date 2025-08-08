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
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED primary key, auto-incrementing

            $table->morphs('tokenable');  // Adds tokenable_id and tokenable_type columns, indexed

            $table->string('name');
            $table->string('token', 64)->unique(); // Ensure unique tokens

            $table->text('abilities')->nullable(); // JSON array of abilities
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
