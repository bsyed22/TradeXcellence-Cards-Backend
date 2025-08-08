<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE deposits ADD COLUMN alias VARCHAR(255) NULL, ADD COLUMN card_type VARCHAR(255) NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE deposits DROP COLUMN alias, DROP COLUMN card_type");
    }
};
