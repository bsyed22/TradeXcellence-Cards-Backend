<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Setting::all(),
        ]);
    }

    public function show($key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['success' => false, 'message' => 'Setting not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $setting]);
    }

    public function storeOrUpdate(Request $request)
    {
        $data = $request->all();

        if (!is_array($data)) {

            return response()->error("Request must be an array of settings.",422);
        }

        $errors = [];
        $saved = [];

        foreach ($data as $index => $settingData) {
            $validator = Validator::make($settingData, [
                'key' => 'required|string',
                'value' => 'nullable',
            ]);

            if ($validator->fails()) {
                $errors[$index] = $validator->errors();
                continue;
            }

            $validated = $validator->validated();

            $saved[] = Setting::updateOrCreate(
                ['key' => $validated['key']],
                ['value' => $validated['value']]
            );
        }

        if (!empty($errors)) {

            return response()->error('Some settings failed to save.', $errors,422);
        }

        return response()->success($saved,"Setting saved successfully",200);
    }

    public function destroy($key)
    {
        $deleted = Setting::where('key', $key)->delete();

        if (!$deleted) {
            return response()->error("Setting not found");
        }

        return response()->success(null,"setting deleted",200);
    }
}
