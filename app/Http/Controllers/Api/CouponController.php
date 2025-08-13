<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    public function index()
    {
        return response()->success(Coupon::all(), "List Coupon", 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:coupons,code',
            'is_single_use' => 'required|boolean',
            'discount_type' => 'required|string',
            'redemption_quantity' => 'required|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'discount_value' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->error($validator->errors(), "Validation Error", 422);
        }


        $coupon = Coupon::create($validator->validated());
        return response()->success($coupon, "Create Coupon", 201);
    }

    public function show($id)
    {
        $coupon = Coupon::findOrFail($id);
        return response()->success($coupon, "Show Coupon", 200);
    }

    public function validateCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:coupons,code',
            'user_id' => 'required|integer|exists:users,id',
        ]);


        if ($validator->fails()) {
            return response()->error($validator->errors(), "Validation Error", 422);
        }

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->error(null, "Coupon Not Found", 404);
        }

        if ($coupon->status !== "active") {
            return response()->error(null, "Coupon is not Active", 400);
        }

        // Check global redemption limit
        if (!is_null($coupon->redemption_quantity) &&
            $coupon->consumed_quantity >= $coupon->redemption_quantity) {
            return response()->error(null, "Coupon has reached its maximum redemption limit.", 400);
        }

        // Check usage limit per user
        if (!is_null($coupon->usage_limit_per_user)) {
            $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $request->user_id)
                ->count();

            if ($userUsageCount >= $coupon->usage_limit_per_user) {
                return response()->error(null, "You have reached the usage limit for this coupon.", 400);
            }
        }

        $coupon = Coupon::where("code",$request->code)->first();
        return response()->success($coupon, "Coupon Details", 200);
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'nullable|string|unique:coupons,code,' . $coupon->id,
            'is_single_use' => 'nullable|boolean',
            'status' => 'nullable|string',
            'discount_type' => 'nullable|string|in:percentage,fixed',
            'redemption_quantity' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'discount_value' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->error($validator->errors(), "Validation Error", 422);
        }

        $coupon->update($validator->validated());

        return response()->success($coupon, "Coupon updated successfully", 200);
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->success($coupon, "Coupon Deleted Successfully", 200);
    }

    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:coupons,code',
            'user_id' => 'required|integer|exists:users,id',
        ]);


        if ($validator->fails()) {
            return response()->error($validator->errors(), "Validation Error", 422);
        }

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->error(null, "Coupon Not Found", 404);
        }

        if ($coupon->status !== "active") {
            return response()->error(null, "Coupon is not Active", 400);
        }

        // Check global redemption limit
        if (!is_null($coupon->redemption_quantity) &&
            $coupon->consumed_quantity >= $coupon->redemption_quantity) {
            return response()->error(null, "Coupon has reached its maximum redemption limit.", 400);
        }

        // Check usage limit per user
        if (!is_null($coupon->usage_limit_per_user)) {
            $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $request->user_id)
                ->count();

            if ($userUsageCount >= $coupon->usage_limit_per_user) {
                return response()->error(null, "You have reached the usage limit for this coupon.", 400);
            }
        }

        // Create usage record
        CouponUsage::create([
            'user_id' => $request->user_id,
            'coupon_id' => $coupon->id,
            'discount_amount' => $coupon->discount_value,
            'discount_type' => $coupon->discount_type,
        ]);

// Increment consumed quantity
        $coupon->increment('consumed_quantity');


        return response()->success([
            'coupon' => $coupon->fresh(), // get updated value
            'discount_amount' => $coupon->discount_value,
        ], 200);
    }

}
