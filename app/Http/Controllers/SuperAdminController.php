<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;

class SuperAdminController extends Controller
{
    public function register(Request $request){
        $user1=$request->user();
        $isSuperAdmin = $user1 && $user1->hasRole('super admin');

        Log::info("Logged in user role:",["role"=>$user1->getRoleNames()]);
        $validatorRules=[
            "name"=> "required|string|max:255",
            "email"=> "required|email|unique:users,email",
            "password"=> "required|string|min:6",
        ];
        if($isSuperAdmin){
            $validatorRules["role"]="required|string|exists:roles,name";
        }
        $validator=Validator::make($request->all(),$validatorRules);
        if($validator->fails()){
            return response()->json([
                "messages"=>__("messages.invalid_data"),
                "errors"=>$validator->errors(),
            ],400);
        }

        $user=User::create([
            "name"=> $request->name,
            "email"=> $request->email,
            "password"=> Hash::make($request->password),
        ]);

        if($isSuperAdmin){
            $user->assignRole($request->role);
        }else{
            $user->assignRole("user");
        }
        Log::info('User registered', ['email' => $user->email]);
        if($user->hasRole('super admin') || $user->hasRole('product manager') || $user->hasRole('customer support')||$user->hasRole('admin')||$user->hasRole('discount manager')||$user->hasRole('order manager')){
            $google2fa = new Google2FA();
            $secret = $google2fa->generateSecretKey();
            $user->google2fa_secret = $secret;
            $user->save();
            Log::info('Admin user registered with OTP setup: ' . $user->email);

            return response()->json([
                'message' => __('messages.authorized_registered'),
                'qr_code_url' => $google2fa->getQRCodeUrl('ECommerce', $user->email, $secret),
            ], 201);
        }

        // Generate token for regular users
        $token = $user->createToken('ECommerce')->plainTextToken;
        Log::info('Token created for regular user: ' . $user->email);

        return response()->json([
            'token' => $token,
            'message' => __('messages.user_registered'),
        ], 201);

    }
    public function createRole(Request $request){
        $user=$request->user();
        if(!$user || !$user->hasRole('super admin')){
            return response()->json([
                'messages'=>__('messages.unauthorized'),
            ] ,403);
        }

        $validator=Validator::make($request->all(), [
            'role_name'=>'required|string|unique:roles,name',
        ]);
        if($validator->fails()){
            return response()->json([
                'messages'=>__('messages.invalid_data'),
                'errors'=>$validator->errors(),
            ] ,400);
        }

        $role=Role::create([
            'name'=>$request->role
        ]);

        return response()->json([
            'role'=> $request->$role,
            'message'=>__('messages.role_created'),
        ],201);
    }

    public function updateUserRole(Request $request, $UserId){
        $user=$request->user();
        if(!$user || !$user->hasRole('super admin')){
            return response()->json([
                'messages'=>__('messages.unauthorized'),
            ] ,403);
        }
        $validator=Validator::make($request->all(), [
            'role_name'=> 'required|string|exists:roles,name',
        ]);
        if($validator->fails()){
            return response()->json([
                'messages'=>__('messages.invalid_data'),
                'errors'=>$validator->errors(),
            ] ,400);
        }

        //güncellemek istenen kullanıcıyı bul
        $userUpdate=User::find($UserId);
        if(!$userUpdate){
            return response()->json([
                'messages'=>__('messages.user_not_found'),
            ] ,404);
        }
        $userUpdate->roles()->detach();

        //Yeni rolü ata
        $userUpdate->assignRole($request->role_name);

        return response()->json([
            'user'=> $userUpdate,
            'role'=> $request->role,
            'message'=>__('messages.role_updated'),
        ],200);

    }

}