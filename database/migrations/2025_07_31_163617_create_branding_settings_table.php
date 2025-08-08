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
        Schema::create('branding_settings', function (Blueprint $table) {
            $table->id();

            //logo & favicon
            $table->string('logo_light')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('favicon_light')->nullable();
            $table->string('favicon_dark')->nullable();

            // Typography & Title
            $table->string('site_name')->nullable();
            $table->string('primary_font_url')->nullable();
            $table->string('body_font_family')->nullable();
            $table->string('button_text_light')->nullable();
            $table->string('button_text_dark')->nullable();

            $table->longText('header_raw_content')->nullable();
            $table->longText('footer_raw_content')->nullable();
            $table->string('tawkto_id')->nullable();

            // Banners
            $table->string('desktop_banner')->nullable();
            $table->string('mobile_banner')->nullable();
            $table->text('banner_note')->nullable();

            //loader icons
            $table->text('loader_icon_dark')->nullable();
            $table->text('loader_icon_light')->nullable();

            //login & register side images
            $table->text('auth_screen_banner')->nullable();

            // Card Images
            $table->string('card_front_image')->nullable();
            $table->string('card_back_image')->nullable();

            // App Colors – Dark
            $table->string('dark_primary')->nullable();
            $table->string('dark_primary_hover')->nullable();
            $table->string('dark_table_bg')->nullable();
            $table->string('dark_background')->nullable();
            $table->string('dark_foreground')->nullable();
            $table->string('dark_text')->nullable();
            $table->string('dark_heading')->nullable();

            $table->string('text_primary_light')->nullable();
            $table->string('text_primary_dark')->nullable();
            $table->string('bg_modal_light')->nullable();
            $table->string('bg_modal_dark')->nullable();

            // App Colors – Light
            $table->string('light_primary')->nullable();
            $table->string('light_primary_hover')->nullable();
            $table->string('light_table_bg')->nullable();
            $table->string('light_background')->nullable();
            $table->string('light_foreground')->nullable();
            $table->string('light_text')->nullable();
            $table->string('light_heading')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branding_settings');
    }
};
