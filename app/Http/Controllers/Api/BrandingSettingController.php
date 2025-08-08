<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BrandingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class BrandingSettingController extends Controller
{
    protected $imageManager;
    protected $imageFields = [
        'logo_light',
        'logo_dark',
        'favicon_light',
        'favicon_dark',
        'desktop_banner',
        'mobile_banner',
        'card_front_image',
        'card_back_image',
        'loader_icon_dark',
        'loader_icon_light',
        'auth_screen_banner',
    ];

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    public function index()
    {
        $branding = BrandingSetting::first();

        if ($branding) {
            foreach ($this->imageFields as $field) {
                $branding["{$field}_url"] = $branding->$field
                    ? url(Storage::url("branding/{$branding->$field}"))
                    : null;
            }
        }

        return response()->success($branding, 'Branding settings retrieved successfully');
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Fonts & Text
            'site_name' => 'nullable|string|max:255',
            'primary_font_url' => 'nullable|string|max:255',
            'body_font_family' => 'nullable|string|max:255',
            'banner_note' => 'nullable|string|max:1000',

            // Images
            'desktop_banner' => 'nullable|image|max:5120',
            'mobile_banner' => 'nullable|image|max:5120',
            'card_front_image' => 'nullable|image|max:5120',
            'card_back_image' => 'nullable|image|max:5120',
            'logo_light' => 'nullable|image|max:5120',
            'logo_dark' => 'nullable|image|max:5120',
            'favicon_light' => 'nullable|image|max:5120',
            'favicon_dark' => 'nullable|image|max:5120',

            // Colors
            'dark_primary' => 'nullable|string|max:20',
            'dark_primary_hover' => 'nullable|string|max:20',
            'dark_table_bg' => 'nullable|string|max:20',
            'dark_background' => 'nullable|string|max:20',
            'dark_foreground' => 'nullable|string|max:20',
            'dark_text' => 'nullable|string|max:20',
            'dark_heading' => 'nullable|string|max:20',

            'text_primary_light' => 'nullable|string|max:20',
            'text_primary_dark' => 'nullable|string|max:20',
            'bg_modal_light' => 'nullable|string|max:20',
            'bg_modal_dark' => 'nullable|string|max:20',

            'light_primary' => 'nullable|string|max:20',
            'light_primary_hover' => 'nullable|string|max:20',
            'light_table_bg' => 'nullable|string|max:20',
            'light_background' => 'nullable|string|max:20',
            'light_foreground' => 'nullable|string|max:20',
            'light_text' => 'nullable|string|max:20',
            'light_heading' => 'nullable|string|max:20',

            // Raw content & scripts
            'header_raw_content' => 'nullable|string',
            'footer_raw_content' => 'nullable|string',
            'tawkto_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->error('Validation failed', $validator->errors());
        }

        $branding = BrandingSetting::first() ?? new BrandingSetting();

        $branding->fill($request->except(array_merge($this->imageFields, [
            'header_raw_content', 'footer_raw_content', 'tawkto_id'
        ])));

        $branding->header_raw_content = $request->input('header_raw_content');
        $branding->footer_raw_content = $request->input('footer_raw_content');
        $branding->tawkto_id = $request->input('tawkto_id');

        // Ensure branding directory exists
        Storage::disk('public')->makeDirectory('branding');

        foreach ($this->imageFields as $field) {
            if ($request->hasFile($field)) {
                $uploadedFile = $request->file($field);
                $extension = $uploadedFile->getClientOriginalExtension();
                $imageName = $field . '_' . Str::random(10) . '.' . $extension;

                $image = $this->imageManager->read($uploadedFile);
                $image->save(storage_path("app/public/branding/{$imageName}"));

                $branding->$field = $imageName;
            }
        }

        $branding->save();

        // Attach image URLs
        foreach ($this->imageFields as $field) {
            $branding["{$field}_url"] = $branding->$field
                ? url(Storage::url("branding/{$branding->$field}"))
                : null;
        }


        return response()->success($branding, 'Branding settings updated successfully');
    }
}
