<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Customers;

class CustomerController extends Controller
{
    //
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
        Log::info('User registered', ['email' => $customer->email]);
        $token=$customer->createToken('ECommerce')->plainTextToken;
        Log::info('Token created for regular user'.$customer->email);
        return response()->json([
            'message'=> __('messages.customer_registered'),
            'token'=> $token,

        ],201);

    }
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
        return response()->json([
            'message' => __('messages.login_successfully'),
            'token' => $token,
        ], 200);
    }
}