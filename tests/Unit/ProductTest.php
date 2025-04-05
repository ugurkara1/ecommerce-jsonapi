<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Products;
use App\Models\Brands;
use App\Models\Categories;
use App\Models\Category;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role; // Add this import for the Role class

class ProductTest extends TestCase
{
    // RefreshDatabase trait'ini ekleyin - her testten sonra veritabanını sıfırlayacak
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1) Test veritabanına super admin rolünü ekle
        Role::firstOrCreate([
            'name'       => 'super admin',
            'guard_name' => 'web',
        ]);
        Role::firstOrCreate([
            'name'       => 'product manager',
            'guard_name' => 'web',
        ]);

        // 2) Sonra kullanıcıyı yarat ve rolünü ata
        $superadmin = User::factory()->create();
        $superadmin->assignRole('super admin');
        Sanctum::actingAs($superadmin, ['*']);

        // 3) Geri kalan fixture’lar
        $brand    = Brands::factory()->create(['name' => 'Test Marka']);
        $category = Categories::factory()->create(['name' => 'Test Kategori']);

        $product = Products::factory()->create([
            'sku'         => 'TEST-SKU-001',
            'name'        => 'Test Ürün',
            'description' => 'Test Açıklama',
            'price'       => 199.99,
            'brand_id'    => $brand->id,
            'is_active'   => true,
            'slug'        => 'test-urun',
        ]);

        $product->categories()->attach($category->id);
    }

    public function test_get_all_products_successfully()
    {
        $response = $this->getJson('api/v1/products');

        // Yanıtı Doğrula
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'name',
                        'description',
                        'price',
                        'brand_id',
                        'is_active',
                        'slug',
                        'brands',
                        'categories',
                        'variants',
                    ]
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertEquals('Test Ürün', $responseData[0]['name']);
        $this->assertEquals('TEST-SKU-001', $responseData[0]['sku']);
    }

    public function test_show_product_successfully()
    {
        $product = Products::where('name', 'Test Ürün')->first();
        $response = $this->getJson("api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'sku',
                    'name',
                    'description',
                    'price',
                    'brand_id',
                    'is_active',
                    'slug',
                    'brands',
                    'categories',
                    'variants',
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertEquals('Test Ürün', $responseData['name']);
        $this->assertEquals('TEST-SKU-001', $responseData['sku']);
    }

    public function test_create_product_successfully()
    {
        $brand = Brands::first();
        $category = Categories::first();

        $productData = [
            'sku' => 'NEW-SKU-001',  // Farklı bir SKU kullanın
            'name' => 'Yeni Ürün',
            'description' => 'Yeni Ürün Açıklaması',
            'price' => 299.99,
            'brand_id' => $brand->id,
            'is_active' => true,
            'slug' => 'yeni-urun',
            'category_ids' => [$category->id]
        ];

        // API isteği yap
        $response = $this->postJson('api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'sku',
                    'name',
                    'description',
                    'price',
                    'brand_id',
                    'is_active',
                    'slug'
                ]
            ]);

        // Veritabanında doğrula
        $this->assertDatabaseHas('products', [
            'sku' => 'NEW-SKU-001',
            'name' => 'Yeni Ürün',
            'price' => 299.99
        ]);

        // Kategori ilişkisini doğrula
        $newProduct = Products::where('sku', 'NEW-SKU-001')->first();
        $this->assertTrue($newProduct->categories->contains($category->id));
    }

    public function test_update_product_successfully()
    {
        $product = Products::where('name', 'Test Ürün')->first();
        $brand = Brands::first();
        $category = Categories::first();
        $newCategory = Categories::factory()->create();

        $updateData = [
            'sku' => 'UPD-SKU-001',
            'name' => 'Güncellenmiş Ürün',
            'description' => 'Güncellenmiş Açıklama',
            'price' => 399.99,
            'brand_id' => $brand->id,
            'is_active' => true,
            'slug' => 'guncellenmis-urun',
            'category_ids' => [$category->id, $newCategory->id]
        ];

        $response = $this->putJson("api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'sku',
                    'name',
                    'description',
                    'price',
                    'brand_id',
                    'is_active',
                    'slug'
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertEquals('Güncellenmiş Ürün', $responseData['name']);
        $this->assertEquals('UPD-SKU-001', $responseData['sku']);
        $this->assertEquals(399.99, $responseData['price']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Güncellenmiş Ürün',
            'sku' => 'UPD-SKU-001',
            'price' => 399.99
        ]);

        // Kategori ilişkisini doğrula
        $updatedProduct = Products::find($product->id);
        $this->assertTrue($updatedProduct->categories->contains($category->id));
        $this->assertTrue($updatedProduct->categories->contains($newCategory->id));
        $this->assertEquals(2, $updatedProduct->categories->count());
    }

    public function test_delete_product_successfully()
    {
        $product = Products::where('name', 'Test Ürün')->first();
        $response = $this->deleteJson("api/v1/products/{$product->id}");

        // Yanıt doğrula
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => __('messages.product_deleted')
            ]);
            $this->assertDatabaseMissing('products', [
                'id' => $product->id
            ]);
    }

    public function test_cannot_update_non_existent_product()
    {
        $nonExistentId = 999999; // Büyük bir ID seçerek var olmayan bir kayıt simüle edilir.

        $updateData = [
            'sku' => 'INV-SKU-001',
            'name' => 'Geçersiz Ürün',
            'price' => 199.99,
            'brand_id' => Brands::first()->id
        ];

        $response = $this->putJson("api/v1/products/{$nonExistentId}", $updateData);

        // Yanıtın 404 döndürdüğünü doğrula
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => __('messages.product_not_found')
            ]);
    }

    public function test_cannot_delete_non_existent_product()
    {
        $invalidId = 999999; // Var olmayan bir ID

        $response = $this->deleteJson("api/v1/products/{$invalidId}");

        // Yanıtın 404 döndürdüğünü doğrula
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => __('messages.product_not_found')
            ]);
    }

    public function test_cannot_create_duplicate_sku()
    {
        $brand = Brands::first();

        // Önce bir test ürünü olduğundan emin olalım
        $existingProduct = Products::where('sku', 'TEST-SKU-001')->first();
        $this->assertNotNull($existingProduct);

        // Zaten var olan SKU ile yeni bir ürün oluşturmaya çalış
        $productData = [
            'sku' => 'TEST-SKU-001', // Zaten mevcut
            'name' => 'Duplicate SKU Ürün',
            'description' => 'Duplicate Açıklama',
            'price' => 199.99,
            'brand_id' => $brand->id,
            'is_active' => true,
            'slug' => 'duplicate-urun'
        ];

        // API isteği yap
        $response = $this->postJson('api/v1/products', $productData);

        // Yanıtı doğrula (hata bekleniyor)
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }

    public function test_cannot_create_product_with_invalid_data()
    {
        // Geçersiz ürün verileri (required alanlar eksik)
        $productData = [
            'description' => 'Test Açıklama'
            // SKU, name, price gibi zorunlu alanlar eksik
        ];

        // API isteği yap
        $response = $this->postJson('api/v1/products', $productData);

        // Yanıtı doğrula (hata bekleniyor)
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }

    public function test_non_admin_user_cannot_delete_product()
    {
        // Admin olmayan kullanıcı oluştur
        $user = User::factory()->create();
        $user->assignRole('product manager'); // Silme yetkisi olmayan bir rol
        Sanctum::actingAs($user, ['*']);

        $product = Products::where('name', 'Test Ürün')->first();

        $response = $this->deleteJson("api/v1/products/{$product->id}");

        $response->assertStatus(403); // Yetkisiz işlem olmalı
    }

    public function test_product_manager_can_update_product()
    {
        // Product manager rolündeki kullanıcı
        $user = User::factory()->create();
        $user->assignRole('product manager');
        Sanctum::actingAs($user, ['*']);

        $product = Products::where('name', 'Test Ürün')->first();
        $updateData = [
            'name' => 'Product Manager Updated',
            'price' => 499.99,
            'brand_id' => Brands::first()->id,
            'sku' => $product->sku // aynı SKU kullanılabilir güncelleme sırasında
        ];

        $response = $this->putJson("api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => __('messages.product_updated')
            ]);
    }

    public function test_create_product_with_nonexistent_brand()
    {
        $nonExistentBrandId = 999999;

        $productData = [
            'sku' => 'BRAND-SKU-001',
            'name' => 'Invalid Brand Product',
            'price' => 199.99,
            'brand_id' => $nonExistentBrandId,
            'is_active' => true
        ];

        $response = $this->postJson('api/v1/products', $productData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }

    public function test_create_product_with_nonexistent_category()
    {
        $brand = Brands::first();
        $nonExistentCategoryId = 999999;

        $productData = [
            'sku' => 'CAT-SKU-001',
            'name' => 'Invalid Category Product',
            'price' => 199.99,
            'brand_id' => $brand->id,
            'is_active' => true,
            'category_ids' => [$nonExistentCategoryId]
        ];

        $response = $this->postJson('api/v1/products', $productData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }
}