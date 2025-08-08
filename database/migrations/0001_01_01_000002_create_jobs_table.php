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
        Schema::create('jobs', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->unsigned(); // Use INT UNSIGNED for id
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->unsignedInteger('total_jobs'); // Use unsigned integer for total_jobs
            $table->unsignedInteger('pending_jobs'); // Use unsigned integer for pending_jobs
            $table->unsignedInteger('failed_jobs'); // Use unsigned integer for failed_jobs
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->unsignedInteger('cancelled_at')->nullable(); // Use unsigned integer for cancelled_at
            $table->unsignedInteger('created_at');
            $table->unsignedInteger('finished_at')->nullable(); // Use unsigned integer for finished_at
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->unsigned(); // Use INT UNSIGNED for id
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
