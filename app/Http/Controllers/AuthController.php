<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    //
    public function register(Request $request){
        $validator=Validator::make($request->all(),[
            'name'=>'required|string|max:255',
            'email'=> 'required|email|unique:users,email',
            'password'=> 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'messages' => __('messages.invalid_data'),
                'errors' => $validator->errors() // Virgülü kaldırdık
            ], 422);
        }
        $user=User::create([
            'name'=> $request->name,
            'email'=> $request->email,
            'password'=> Hash::make($request->password),
        ]);
        $user->assignRole('super admin');
        Log::info('User registered', ['email' => $user->email]);
        if($user->hasRole('super admin')|| $user->hasRole('product manager') || $user->hasRole('customer support')|| $user->hasRole('admin') || $user->hasRole('product manager') || $user->hasRole('discount manager') || $user->hasRole('order manager')){
            $google2fa = new Google2FA();
            $secret=$google2fa->generateSecretKey();
            $user->google2fa_secret=$secret;
            $user->save();
            Log::info('Admin user registered with OTP setup:'. $user->email);

            return response()->json([
                'message'=>__('messages.authorized_registered'),
                'qr_code_url'=>$google2fa->getQRCodeUrl('ECommerce',$user->email,$secret),
            ],201);
            //admin değilse token oluştur
        }
        $token=$user->createToken('ECommerce')->plainTextToken;
        Log::info('Token created for regular user'.$user->email);

        return response()->json([
            'token'=> $token,
            'message'=>__('messages.user_registered'),
        ],201);


    }
    public function login(Request $request)
    {
        // Rate limiting ayarları
        $maxAttempts  = 5; //max deneme sayısı
        $decayMinutes = 1; //1dk içinde deneme
        $throttleKey  = Str::lower($request->input('email')) . '|' . $request->ip();

        // Eğer çok fazla başarısız deneme varsa, 429 yanıtı dönüyoruz
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            Log::warning('Too many login attempts for email: ' . $request->input('email'));
            return response()->json(['message' => __('messages.too_many_attempts')], 429);
        }

        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            Log::warning('Login failed: Validation error for email: ' . $request->input('email'));
            return response()->json([
                'message' => __('messages.invalid_data'),
                'errors'  => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Hatalı girişte rate limiter sayaç artışı
            RateLimiter::hit($throttleKey, $decayMinutes * 60);
            Log::warning('Unauthorized login attempt for email: ' . $request->email);
            return response()->json(['message' => __('messages.invalid_credentials')], 401);
        }

        // Başarılı girişte, rate limiter sayaç sıfırlanır
        RateLimiter::clear($throttleKey);
        Log::info('Login successful for user: ' . $user->email);

        // Admin yetkileri kontrolü
        if ($user->hasRole('super admin') || $user->hasRole('product manager') || $user->hasRole('customer support')) {
            // Eğer kullanıcıda google2fa_secret yoksa, oluşturuyoruz
            if (empty($user->google2fa_secret)) {
                $google2fa = new Google2FA();
                $secret    = $google2fa->generateSecretKey();
                $user->google2fa_secret = $secret;
                $user->save();
                Log::info('New OTP secret key generated for user: ' . $user->email);
            }

            Log::info('Admin user logged in, OTP verification required: ' . $user->email);

            // QR kodu oluşturma işlemi
            $google2fa = new Google2FA();
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                'ECommerce',
                $user->email,
                $user->google2fa_secret
            );

            // Nonce oluşturma ve token payload
            $nonce   = Str::random(32);
            $payload = [
                'id'    => $user->id,
                'nonce' => $nonce,
            ];
            $base64Token = base64_encode(json_encode($payload));

            return response()->json([
                'message'      => __('messages.otp_required'),
                'require_otp'  => true,
                'qr_code_url'  => $qrCodeUrl,
                'base64_token' => $base64Token,
            ], 200);
        }

        // Normal kullanıcı için token oluşturma
        $token = $user->createToken('ECommerce')->plainTextToken;
        return response()->json(['token' => $token], 200);
    }

    public function verifyOtp(Request $request){
        $request->validate([
            'otp' => 'required|numeric',
            'base64_token' => 'required|string', // Corrected parameter name
        ]);

        $decodedToken = json_decode(base64_decode($request->base64_token), true); // Decode as associative array

        if (!$decodedToken || !isset($decodedToken['id'])) {
            Log::warning('OTP verification failed: Invalid token payload');
            return response()->json(['message' => __('messages.invalid_token')], 400);
        }

        $user = User::find($decodedToken['id']);
        if (!$user) {
            Log::warning('OTP verification failed: User not found for id ' . $decodedToken['id']);
            return response()->json(['message' => __('messages.user_not_found')], 404);
        }
        // OTP doğrulaması
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);

        if ($valid) {
            // OTP başarılıysa kullanıcı için API token'ı oluşturuyoruz
            $token = $user->createToken('ECommerce')->plainTextToken;
            Log::info('OTP verified successfully for user: ' . $user->email);
            return response()->json(['token' => $token], 200);
        } else {
            Log::warning('Invalid OTP entered for user: ' . $user->email);
            return response()->json(['message' => __('messages.invalid_otp')], 400);
        }
    }
}