<?php

namespace Tests\Unit;
use App\Models\Customers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function PHPUnit\Framework\assertJson;
use Spatie\Permission\Models\Role;


class UserTest extends TestCase{
    protected Customers $user;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        // Test veritabanında rollerin var olduğundan emin oluyoruz
        if (!Role::where('name', 'super admin')->exists()) {
            Role::create(['name' => 'super admin', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'product manager')->exists()) {
            Role::create(['name' => 'product manager', 'guard_name' => 'web']);
        }

        //Normal kullanıcı oluşturuyoruz
        $this->user=Customers::factory()->create([
            'email'=>'user@example.com',
            'password'=>Hash::make('password123')
        ]);
        // Admin rolüne sahip kullanıcı oluşturuyoruz
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123')
        ]);
        $this->adminUser->assignRole('super admin');
    }
    //Normal kullanıcı kaydının başarılı olduğunu test eder
    public function test_user_registration_successful(){
        $response=$this->postJson('api/v1/customer/register',[

            'email'=>'newuser@example.com',
            'password'=>'password'
        ]);
        $response->assertStatus(201)
            ->assertJsonStructure(['token','message']);

        //Kullanıcının veritabanına eklenip eklenmediğini kontrol ediyoruz
        $this->assertDatabaseHas('customers',[
            'email'=>'newuser@example.com'
        ]);
    }

}
