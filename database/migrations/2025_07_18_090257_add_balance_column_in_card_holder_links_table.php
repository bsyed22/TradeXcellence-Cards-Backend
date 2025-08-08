<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('card_holder_links', function (Blueprint $table) {
            $table->decimal('balance', 18, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('card_holder_links', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};
