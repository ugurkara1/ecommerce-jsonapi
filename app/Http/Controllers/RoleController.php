<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{


    //ROL ATAMA
    public function assignRole(Request $request){
        //Gelen verilerin doğrulanması
        $validator=Validator::make($request->all(),[
            'user_id'=>'required|exists:users,id',
            'role'=>'required|string|exists:roles,name'
        ]);
        if($validator->fails()){
            return response()->json([
                'messages'=>__('messages.invalid_data'),
                'errors'=>$validator->errors(),
            ] ,400);
        }
        $currentUser=$request->user();
        Log::info('Current user roles: ', $currentUser->getRoleNames()->toArray());

        if(!$currentUser || !$currentUser->hasRole('super admin')){
            return response()->json([
                'messages'=>__('messages.unauthorized'),
            ] ,403);
        }

        $targetUser=User::find($request->user_id);
        if(!$targetUser){
            return response()->json([
                'messages'=>__('messages.user_not_found'),
            ] ,404);
        }
        $targetUser->syncRoles([$request->role]);
        Log::info('Super admin assigned role ' . $request->role . ' to user: ' . $targetUser->email);
        return response()->json([
            'messages'=>__('messages.assigned'),
            'user'=>$targetUser
        ] ,200);

    }
    public function createPermissions(Request $request)
    {
        // Verilerin doğrulanması
        $validator = Validator::make($request->all(), [
            'permission_name' => 'required|string|unique:permissions,name',
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'messages' => __('messages.invalid_data'),
                'errors' => $validator->errors(),
            ], 400);
        }

        // Giriş yapan kullanıcıyı al
        $currentUser = $request->user();

        // Giriş yapan kullanıcı 'super admin' değilse yetkisiz erişim
        if (!$currentUser || !$currentUser->hasRole('super admin')) {
            return response()->json([
                'messages' => __('messages.unauthorized'),
            ], 403);
        }

        try {
            // Permission oluşturuluyor, guard_name 'web' olarak ayarlandı
            $permission = Permission::create([
                'name' => $request->permission_name,
                'guard_name' => 'web', // Guard name: web
            ]);

            // İlgili rolü 'web' guard'ında al
            $role = Role::findByName($request->role, 'web'); // Guard parametresi eklendi

            // Rol ile izni ilişkilendir
            $role->givePermissionTo($permission);

            Log::info('Super admin created permission: ' . $request->permission_name . ' and assigned it to role: ' . $role->name);

            return response()->json([
                'messages' => __('messages.permission_created'),
                'permission' => $permission,
                'role' => $role,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating permission: ' . $e->getMessage());
            return response()->json([
                'messages' => __('messages.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }



}
