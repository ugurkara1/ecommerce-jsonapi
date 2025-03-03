<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // İzinleri oluştur
        $permissions = [
            'manage users',
            'add products',
            'update products',
            'delete products',
            'view products',
            'manage roles and permissions',
            'added products',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Rolleri oluştur
        $superAdmin = Role::create(['name' => 'super admin']);
        $productManager = Role::create(['name' => 'product manager']);
        $customerSupport = Role::create(['name' => 'customer support']);
        $user=Role::create(['name'=> 'user']);

        // Rolleri izinlerle eşleştir
        $superAdmin->givePermissionTo(Permission::all()); // Super admin tüm izinlere sahip
        $productManager->givePermissionTo([
            'add products', 'update products', 'delete products', 'view products'
        ]); // Ürün sorumlusu izinleri
        $customerSupport->givePermissionTo('view products'); // Müşteri destek sadece ürünleri görebilir
        $user->givePermissionTo(['added products']);
    }
}
