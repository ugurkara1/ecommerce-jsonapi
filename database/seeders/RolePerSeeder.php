<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // İzinleri tanımla
        $permissions = [
            "manage products",
            "manage product variants",
            "manage product images",
            "manage product attributes",
            "view products",
            "manage categories",
            "view categories",
            "manage brands",
            "view brands",
            "manage orders",
            "view orders",
            "view order details",
            "manage discounts",
            "view discounts",
            "manage users",
            "manage roles",
            "view roles",
            "view users",
            "view customer data",
            "manage customer support",
        ];

        // Her bir izni oluştur
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Rolleri oluştur
        $superAdmin = Role::create(['name' => 'super admin']);
        $admin = Role::create(['name' => 'admin']);
        $productManager = Role::create(['name' => 'product manager']);
        $customerSupport = Role::create(['name' => 'customer support']);
        $discountManager = Role::create(['name' => 'discount manager']);
        $orderManager = Role::create(['name' => 'order manager']);

        // Super Admin'e tüm izinleri ver (tüm izinler)
        $superAdmin->givePermissionTo(Permission::all());

        // Admin için izinleri ata
        $admin->givePermissionTo([
            "manage products",
            "manage product variants",
            "manage product images",
            "manage product attributes",
            "view products",
            "manage categories",
            "view categories",
            "manage brands",
            "view brands",
            "manage orders",
            "view orders",
            "view order details",
            "manage discounts",
            "view discounts",
            "manage users",
            "manage roles",
            "view roles",
            "view users",
            "view customer data",
            "manage customer support",
        ]);

        // Product Manager için izinler
        $productManager->givePermissionTo([
            "manage products",
            "manage product variants",
            "manage product images",
            "manage product attributes",
            "view products",
            "manage categories",
            "view categories",
            "manage brands",
            "view brands",
        ]);

        // Customer Support için izinler
        $customerSupport->givePermissionTo([
            "view orders",
            "view order details",
            "view users",
            "view customer data",
            "manage customer support",
        ]);

        // Discount Manager için izinler
        $discountManager->givePermissionTo([
            "manage discounts",
            "view discounts",
        ]);

        // Order Manager için izinler
        $orderManager->givePermissionTo([
            "manage orders",
            "view orders",
            "view order details",
        ]);
    }
}