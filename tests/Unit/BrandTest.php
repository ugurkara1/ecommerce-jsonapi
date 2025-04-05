<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use App\Models\Brands;

class BrandTest extends TestCase{
    protected function setUp(): void
    {
        parent::setUp();

        // Test kullanıcısı oluşturma ve rol verme (spatie/laravel-permission kullanıldığını varsayıyoruz)
        $superadmin = User::factory()->create();
        $superadmin->assignRole('super admin');
        Sanctum::actingAs($superadmin, ['*']);

        // Test markası oluştur
        Brands::factory()->create([
            'name' => 'Test Marka',
            'logo_url' => 'https://example.com/logo.png'
        ]);
    }
    public function test_get_all_brands_successfully()
    {
        $response=$this->getJson('api/v1/brands');

        //Yanıtı Doğrula
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    '*'=>[
                        'id',
                        'name',
                        'logo_url'
                    ]
                ]
            ]);

            $responseData=$response->json('data');
            $this->assertEquals('Test Marka',$responseData[0]['name']);
            $this->assertEquals('https://example.com/logo.png',$responseData[0]['logo_url']);

    }
    //Marka ekleme test
    public function test_create_brand_successfully(){
        $brandData=[
            'name'=>'Yeni Marka',
            'logo_url' => 'https://example.com/yeni-logo.png'
        ];

        //API isteği yap

        $response=$this->postJson('api/v1/brands',$brandData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'name',
                    'logo_url'
                ]
            ]);

       // Veritabanında doğrula
        $this->assertDatabaseHas('brands', [
            'name' => 'Yeni Marka',
            'logo_url' => 'https://example.com/yeni-logo.png'
        ]);
    }

    public function test_update_brand_successfully(){
        $brand=Brands::where('name','Test Marka')->first();

        $updateData=[
            'name'=>"Güncellenmiş Marka",
            'logo_url' => 'https://example.com/updated-logo.png'
        ];

        $response=$this->putJson("api/v1/brands/{$brand->id}",$updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'name',
                    'logo_url'
                ]
            ]);

        $responseData=$response->json('data');
        $this->assertEquals('Güncellenmiş Marka',$responseData['name']);
        $this->assertEquals('https://example.com/updated-logo.png', $responseData['logo_url']);


        $this->assertDatabaseHas('brands',[
            'id'=>$brand->id,
            'name'=>'Güncellenmiş Marka',
            'logo_url'=>'https://example.com/updated-logo.png',
        ]);
    }
    public function test_cannot_update_non_existent_brand()
    {
        $nonExistentId = 999999; // Büyük bir ID seçerek var olmayan bir kayıt simüle edilir.

        $updateData = [
            'name' => 'Geçersiz Marka',
            'logo_url' => 'https://example.com/invalid-logo.png'
        ];

        $response = $this->putJson("api/v1/brands/{$nonExistentId}", $updateData);

        // Yanıtın 404 veya 422 döndürdüğünü doğrula
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => __('messages.brand_not_found')
            ]);
    }


    public function test_delete_brand_successfully(){
        $brand=Brands::where('name','Test Marka')->first();
        $response=$this->deleteJson("api/v1/brands/{$brand->id}");

        //Yanıt doğrula

        $response->assertStatus(200)
            ->assertJson([
                'success'=>true,
                'message'=>__('messages.brand_deleted')
            ]);
        $this->assertDatabaseMissing('brands',[
            'id'=>$brand->id
        ]);
    }
    public function test_delete_brand_with_products()
    {
        $brand = Brands::factory()->create();
        // Ürün ekle
        $brand->products()->create([
            'sku'   => 'TEST-SKU-001',  // Burada sku alanına bir değer sağlıyoruz
            'name'  => 'Test Ürün',
            'price' => 100,
            'stock' => 10
        ]);

        $response = $this->deleteJson("api/v1/brands/{$brand->id}");

        // Yanıtın 422 döndürdüğünü doğrula
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => __('messages.brand_has_products')
            ]);
    }
    public function test_cannot_delete_non_existent_id_attribute()
    {
        $invalidId = 999999; // Var olmayan bir ID

        $response = $this->deleteJson("api/v1/brands/{$invalidId}");

        // Yanıtın 404 döndürdüğünü doğrula
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => __('messages.brand_not_found')
            ]);
    }

    public function test_show_brand_successfully(){
        $brand=Brands::where('name','Test Marka')->first();
        $response=$this->getJson("api/v1/brands/{$brand->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'name',
                    'logo_url',
                    'products'
                ]
            ]);
        $responseData=$response->json('data');
        $this->assertEquals('Test Marka',$responseData['name']);
        $this->assertEquals('https://example.com/logo.png',$responseData['logo_url']);
    }

    //var olan markayla yeni bir marka oluşturmaya çalışma

    public function test_cannot_create_duplicate_brand_name()
    {
        // Zaten var olan marka adıyla yeni bir marka oluşturmaya çalış
        $brandData = [
            'name' => 'Test Marka', // Zaten mevcut
            'logo_url' => 'https://example.com/another-logo.png'
        ];

        // API isteği yap
        $response = $this->postJson('/api/v1/brands', $brandData);

        // Yanıtı doğrula (hata bekleniyor)
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);

        // Hata mesajını kontrol et
        $errors = $response->json('errors');
        $this->assertArrayHasKey('name', $errors);
    }
    public function test_cannot_create_brand_with_invalid_data()
    {
        // Geçersiz marka verileri (name eksik)
        $brandData = [
            'logo_url' => 'https://example.com/logo.png'
        ];

        // API isteği yap
        $response = $this->postJson('/api/v1/brands', $brandData);

        // Yanıtı doğrula (hata bekleniyor)
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);

        // Hata mesajını kontrol et
        $errors = $response->json('errors');
        $this->assertArrayHasKey('name', $errors);
    }
    public function test_non_admin_user_cannot_update_brand()
    {
        $user = User::factory()->create(); // Admin olmayan kullanıcı
        Sanctum::actingAs($user, ['*']);

        $brand = Brands::factory()->create();

        $response = $this->putJson("/api/v1/brands/{$brand->id}", [
            'name' => 'Yetkisiz Güncelleme',
            'logo_url' => 'https://example.com/unauthorized.png'
        ]);

        $response->assertStatus(403); // Yetkisiz işlem olmalı
    }


}