<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use App\Models\CustomerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Customers;
use Jenssegers\Agent\Agent; // Agent sınıfını import edin

class CustomerController extends Controller
{
    //customer register
    public function register(Request $request){
        $validator=Validator::make($request->all(),[
            'email'=> 'required|email|unique:Customers,email',
            'password'=> 'required|string|min:6',
        ]);
        if($validator->fails()){
            return response()->json([
                'messages' => __('messages.invalid_data'),
                'errors' => $validator->errors()

            ], 422);
        }
        $customer=Customers::create([
            'email'=> $request->email,
            'password'=> Hash::make($request->password),
        ]);
        $customer->assignSegment();

        Log::info('User registered', ['email' => $customer->email]);
        $token=$customer->createToken('ECommerce')->plainTextToken;
        Log::info('Token created for regular user'.$customer->email);
        return response()->json([
            'message'=> __('messages.customer_registered'),
            'token'=> $token,

        ],201);

    }

    //customer login
    public function login(Request $request){
        $validator=Validator::make($request->all(),[
            'email'=> 'required|email|exists:customers,email',
            'password'=>'required|string|min:6',
        ]);
        if($validator->fails()){
            return response()->json([
                'message'=> __('messages.invalid_data'),
                'errors'=> $validator->errors(),
            ],422);
        }
        $customer=Customers::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            Log::warning("Unauthorized login attempt for email: " . $request->email);
            return response()->json([
                "message" => __('messages.invalid_credentials'),
            ], 401); // Use 401 for unauthorized
        }
        $token = $customer->createToken('ECommerce')->plainTextToken;

        // Agent bilgilerini al
        $agent = new Agent();
        $platform = $agent->platform();
        $browser = $agent->browser();
        $device = $agent->device();

        Log::info('Agent Detayları:',[
            'platform'=>$platform,
            'browser'=>$browser,
            'device'=>$device,
        ]);
        // LoginHistory kaydı oluştur (ilişki üzerinden)
        $customer->loginHistories()->create([
            'ip_address' => $request->ip(),
            'device' => $device,
            'platform' => $platform,
            'browser' => $browser,
            'login_at' => now(),
        ]);

        return response()->json([
            'message' => __('messages.login_successfully'),
            'token' => $token,
        ], 200);
    }
    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message'=> __('messages.logout_successfully'),
        ],200);
    }
    //customer addProfile
    public function addProfile(Request $request){
        $customer = $request->user();

        if (!$customer) {
            return response()->json([
                'message' => __('messages.customer_not_found'),
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nameSurname' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'birthday' => 'required|date',
            'gender' => 'required|string|in:male,female,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('messages.invalid_data'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $profile = $customer->profile()->updateOrCreate(
            ['customer_id' => $customer->id],
            $request->only(['nameSurname', 'phone', 'weight', 'height', 'birthday', 'gender'])
        );

        return response()->json([
            'message' => __('messages.profile_added'),
            'profile' => $profile,
        ], 201);
    }
    //customer delete
    public function destroyProfiles(Request $request){
        $customer=$request->user();
        if (!$customer) {
            return response()->json([
                'message'=> __('messages.customer_not_found'),
            ],404);
        }


        $customer->profile()->delete();

        return response()->json([
            'message'=> __('messages.profile_deleted'),
        ],200);


    }
    //customer getter
    public function getProfile(Request $request){
        $customer=$request->user();
        if (!$customer) {
            return response()->json([
                'message'=> __('messages.customer_not_found'),
            ],404);
        }
        $profile=CustomerProfile::where('customer_id',$customer->id)->first();
        if (!$profile) {
            return response()->json([
                'message'=> __('messages.profile_not_found'),
            ],404);
        }
        return response()->json([
            'message'=> __('messages.profile_listed'),
            'profile'=> $profile
        ],200);

    }

    //customers adr methods

    public function createAddress(Request $request){
        $customer=$request->user();
        if (!$customer) {
            return response()->json([
                'message'=> __('messages.customer_not_found'),
            ],404);
        }
        $validator=Validator::make($request->all(),[
            'address_line'=> 'required|string|max:255',
            'city'=> 'required|string|max:255',
            'district'=> 'required|string|max:255',
            'postal_code'=> 'required|string|max:10',
        ]);
        if($validator->fails()){
            return response()->json([
                'message'=> __('messages.invalid_data'),
                'errors'=> $validator->errors(),
            ],422);
        }

        $address=CustomerAddress::create([
            'customer_id'=> $customer->id,
            'address_line'=> $request->address_line,
            'city'=> $request->city,
            'district'=> $request->district,
            'postal_code'=> $request->postal_code,
        ]);

        return response()->json([
            'message'=> __('messages.address_added'),
            'address'=> $address,
        ],201);

    }

    public function updateAddress(Request $request){
        $customer=$request->user();
        if (!$customer) {
            return response()->json([
                'message'=> __('messages.customer_not_found'),
            ],404);
        }

        $address=CustomerAddress::where('customer_id',$customer->id)->first();
            // Adres bulunamazsa hata dön
        if (!$address) {
            return response()->json([
                'message' => __('messages.address_not_found'),
            ], 404);
        }
        $validator=Validator::make($request->all(),[
            'address_line'=> 'required|string|max:255',
            'city'=> 'required|string|max:255',
            'district'=> 'required|string|max:255',
            'postal_code'=> 'required|string|max:10',
        ]);
        if($validator->fails()){
            return response()->json([
                'message'=> __('messages.invalid_data'),
                'errors'=> $validator->errors(),
            ],422);
        }
        $address->update([
            'address_line' => $request->address_line,
            'city' => $request->city,
            'district' => $request->district,
            'postal_code' => $request->postal_code,
        ]);

        return response()->json([
            'message'=> __('messages.addresses_updated'),
            'address'=> $address,
        ],200);


    }


    public function deleteAddress(Request $request){
        $customer=$request->user();
        if (!$customer) {
            return response()->json([
                'message'=> __('messages.customer_not_found'),
            ],404);
        }

        $address=CustomerAddress::where('customer_id',$customer->id)->first();

        if (!$address) {
            return response()->json([
                'message'=> __('messsages.address_not_found'),
            ],404);
        }

        $address->delete();
        return response()->json([
            'message'=> __('messages.deleted_customer'),
            'address'=> $address,
        ],200);
    }

    public function index(Request $request){
        $customer=$request->user();
        if (!$customer) {
            return response()->json([
                'message'=> __('messages.customer_not_found'),
            ],404);
        }
        $addresses = CustomerAddress::where('customer_id', $customer->id)->get();
        // Adres bulunmazsa
        if ($addresses->isEmpty()) {
            return response()->json([
                'message' => __('messages.no_address_found'),
            ], 404);
        }

        return response()->json([
            'message' => __('messages.customer_address_listed'),
            'addresses' => $addresses,
        ], 200);
    }


}