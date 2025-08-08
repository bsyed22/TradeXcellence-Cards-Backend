<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement('ALTER TABLE card_holder_links MODIFY card_number BIGINT UNSIGNED NULL;');
    }

    public function down()
    {
        DB::statement('ALTER TABLE card_holder_links MODIFY card_number BIGINT UNSIGNED NOT NULL;');
    }
};
