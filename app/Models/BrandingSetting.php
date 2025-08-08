<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BrandingSetting extends Model
{
    protected $fillable = [

        'logo',
        'favicon_light',
        'favicon_dark',

        'site_name',

        'primary_font_url',
        'body_font_family',
        'banner_note',

        'desktop_banner',
        'mobile_banner',
        'card_front_image',
        'card_back_image',

        'loader_icon_dark',
        'loader_icon_light',
        'auth_screen_banner',

        'button_text_light',
        'button_text_dark',


        'text_primary_light',
        'text_primary_dark',
        'bg_modal_light',
        'bg_modal_dark',


        'header_raw_content',
        'footer_raw_content',
        'tawkto_id',

        'dark_primary',
        'dark_primary_hover',
        'dark_table_bg',
        'dark_background',
        'dark_foreground',
        'dark_text',
        'dark_heading',

        'light_primary',
        'light_primary_hover',
        'light_table_bg',
        'light_background',
        'light_foreground',
        'light_text',
        'light_heading',
    ];

}
