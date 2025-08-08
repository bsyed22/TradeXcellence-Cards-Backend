<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminActivatePhysicalCard;
use App\Mail\AdminCardCreated;
use App\Mail\AdminDepositSubmitted;
use App\Mail\AdminPhysicalCardRequest;
use App\Mail\AdminUserRegistered;
use App\Mail\UserActivatePhysicalCard;
use App\Mail\UserCardCreated;
use App\Mail\UserPhysicalCardRequest;
use App\Models\CardHolderLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Pulse\Livewire\Card;
use function Laravel\Prompts\error;

class CardHolderLinkController extends Controller
{
    public function index()
    {
//        return response()->success(CardHolderLink::all());
        return response()->success(CardHolderLink::with('user')->get(), "Card Holder Link List", 200);
    }

    public function cardsByCardHolderLinkId($id)
    {
        $data = CardHolderLink::where("card_holder_id", $id)->get();
        if ($data) {
            return response()->success($data, "Card Holder Link List", 200);
        } else {
            return response()->error("Card Holder Link List", 404);
        }
    }


    public function store(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'card_id' => 'nullable|integer',
            'card_holder_id' => 'required|integer',
            'card_number' => 'nullable|integer',
            'card_holder_name' => 'required|string',
            'type' => 'required|string',
            'alias' => 'required|string',
            'email' => 'required|string',
            'fee_paid' => 'nullable|boolean',
            'balance' => 'nullable|integer',
            'status' => 'nullable|string',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validated = $validator->validated();

            $link = new CardHolderLink();
            $link->card_id = $validated['card_id'] ?? null;
            $link->card_holder_id = $validated['card_holder_id'];
            $link->card_number = $validated['card_number'] ?? null;
            $link->email = $validated['email'];
            $link->type = $validated['type'];
            $link->card_holder_name = $validated['card_holder_name'];
            $link->alias = $validated['alias'];
            $link->balance = $validated['balance'] ?? 0; // or null if preferred
            $link->status = $validated['status'] ?? null;
            $link->fee_paid = $validated['fee_paid'] ?? false;

            $link->save();

            // Get all users with 'admin' role
            $admins = User::role('admin')->get(); // works with Spatie\Permission\Traits\HasRoles

            foreach ($admins as $admin) {
                if (!filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                try {
                    DB::table('notifications')->insert([
                        'id' => Str::uuid(),
                        'type' => 'manual', // optional, can be custom string
                        'notifiable_type' => \App\Models\User::class,
                        'notifiable_id' => $admin->id,
                        'data' => json_encode([
                            'title' => 'New '.$link->type. "created",
                            'message' => $link->card_holder_name.'has created a new .'.$link->type." card. The card id is:".$link->card_id,
                            'action_url' => '/deposits/123'
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Mail::to($admin->email)->send(new AdminCardCreated($link));

                } catch (\Exception $e) {

                }
            }

            Mail::to($link->email)->send(new UserCardCreated($link));


            return response()->success($link, "Card Holder Link Added", 201);

        } catch (\Exception $e) {

            return response()->error("Something went wrong", $e->getMessage(),500);
        }
    }

    public function requestPhysicalCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'physical_card_holder_id' => 'required|exists:users,physical_card_holder_id',
            'alias' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->error('Validation Error', $validator->errors(), 422);
        }

        $link = CardHolderLink::create([
            'card_holder_id' => $request->physical_card_holder_id,
            'alias' => $request->alias,
            'card_holder_name' => $request->card_holder_name,
            'email' => $request->email,
            'type' => "physical",
            'balance' => 0,
            'status' => 'pending',
        ]);


        $admins = User::role('admin')->get(); // works with Spatie\Permission\Traits\HasRoles
        $user = User::where("physical_card_holder_id",$request->physical_card_holder_id)->first();

        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'manual', // optional, can be custom string
            'notifiable_type' => \App\Models\User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Physical Card Request',
                'message' => 'Your Card Request Has Been Received',
                'action_url' => '/deposits/123'
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        foreach ($admins as $admin) {
            if (!filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                DB::table('notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => 'manual', // optional, can be custom string
                    'notifiable_type' => \App\Models\User::class,
                    'notifiable_id' => $admin->id,
                    'data' => json_encode([
                        'title' => 'New Physical Card Request',
                        'message' => 'Request of physical card from.'.$user->name,
                        'action_url' => '/deposits/123'
                    ]),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Mail::to($admin->email)->send(new AdminPhysicalCardRequest($link));

            } catch (\Exception $e) {

            }
        }

        Mail::to($user->email)->send(new UserPhysicalCardRequest($link));

        return response()->success($link, "Physical Card Request Submitted", 201);
    }

    public function userPhysicalCardRequests($id)
    {
        $data = CardHolderLink::where(["card_holder_id"=>$id,"status"=>"pending"])->get();
        return response()->success($data, "User Physical Card Requests", 200);

    }

    public function physicalCardRequests()
    {
        return response()->success(CardHolderLink::where("type","physical")->get(), "Physical Card Requests", 200);
    }
    public function physicalCardRequestsStatusUpdate(Request $request,$id)
    {
        $data = CardHolderLink::find($id);
        if(!$data)
        {
            return  response()->error("Physical Card Request Not Found", 404);
        }
        $data->status = $request->status;
        $data->save();

        return response()->success($data, "Physical Card Status Updated", 200);

    }

    public function activateCard(Request $request, $id)
    {
        $validated = $request->validate([
            'last_four' => 'required|digits:4',
        ]);

        $link = CardHolderLink::findOrFail($id);

        if ((string)substr($link->card_number, -4) !== (string)$validated['last_four']) {
            return response()->error("Last 4 digits do not match.", 422);
        }


        $link->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $admins = User::role('admin')->get(); // works with Spatie\Permission\Traits\HasRoles
        $user = User::where("physical_card_holder_id",$request->physical_card_holder_id)->first();



        foreach ($admins as $admin) {
            if (!filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                DB::table('notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => 'manual', // optional, can be custom string
                    'notifiable_type' => \App\Models\User::class,
                    'notifiable_id' => $admin->id,
                    'data' => json_encode([
                        'title' => 'Deposit Request',
                        'message' => 'New Deposit Request Received from.'.$user->name,
                        'action_url' => '/deposits/123'
                    ]),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Mail::to($admin->email)->send(new AdminActivatePhysicalCard($link));

            } catch (\Exception $e) {

            }
        }

        Mail::to($user->email)->send(new UserActivatePhysicalCard($link));

        return response()->json(['success' => true, 'message' => 'Card activated successfully.']);
    }


    public function show($id)
    {
        $link = CardHolderLink::findOrFail($id);

        return response()->success($link, "Card Holder Link Detail", 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'card_id' => 'nullable|integer',
            'card_holder_id' => 'nullable|integer',
            'card_number' => 'nullable|integer',
            'card_holder_name' => 'nullable|string',
            'type' => 'nullable|string',
            'alias' => 'nullable|string',
            'email' => 'nullable|email',
            'fee_paid' => 'nullable|boolean',
            'balance' => 'nullable|integer',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {

            return response()->error("Validation Error", $validator->errors(),422);
        }

        $link = CardHolderLink::find($id);

        if (!$link) {
            return response()->error("Card Holder Link Not Found", 404);
        }

        // Only update validated data
        $link->update($validator->validated());

        return response()->success($link, "Card Holder Link Updated", 200);
    }

    public function destroy($id)
    {
        $link = CardHolderLink::findOrFail($id);
        $link->delete();

        return response()->success(null,"Card Holder Link Deleted", 200);
    }
}
