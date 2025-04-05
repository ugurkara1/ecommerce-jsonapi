<?php

namespace Tests\Unit;
use App\Models\Categories;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class CategoryTest extends TestCase{
    protected $admin;
    protected $parentCategory;
    protected $childCategory;
    protected function setUp(): void {

        parent::setUp();

        $superadmin=User::factory()->create();
        $superadmin->assignRole('super admin');
        Sanctum::actingAs($superadmin,['*']);

        $this->parentCategory = Categories::factory()->create([
            'name' => 'Ana Kategori',
            'slug' => 'ana-kategori-' . uniqid(),
        ]);

        $this->childCategory = Categories::factory()->create([
            'name' => 'Alt Kategori',
            'slug' => 'alt-kategori-' . uniqid(),
            'parent_category_id' => $this->parentCategory->id
        ]);

    }

    public function test_get_all_categories_successfully(){
        $response = $this->getJson('api/v1/categories');

        // Yanıtın temel yapısını doğrula
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'parent_category_id',
                        'children'
                    ]
                ]
            ]);

        // Ek doğrulamalar
        $responseData = $response->json('data');
        // Dönen verinin bir dizi olduğunu doğrula
        $this->assertIsArray($responseData);
        // En az 2 kategori olması bekleniyor (ana ve alt kategori)
        $this->assertGreaterThanOrEqual(2, count($responseData), "En az iki kategori bekleniyor");

        $parentCategoryFound = false;
        foreach ($responseData as $category) {
            if ($category['name'] === 'Ana Kategori') {
                $parentCategoryFound = true;
                // Slug bilgisinde "ana-kategori" içermesi doğrulanıyor
                $this->assertStringContainsString('ana-kategori', $category['slug']);

                // Alt kategorilerin dizisinin array olduğunu doğrula
                $this->assertIsArray($category['children']);
                // Eğer alt kategoriler varsa "Alt Kategori"nin bulunduğunu doğrula
                if (count($category['children']) > 0) {
                    $childNames = array_column($category['children'], 'name');
                    $this->assertContains('Alt Kategori', $childNames);
                }
            }
        }
        $this->assertTrue($parentCategoryFound, 'Parent category "Ana Kategori" was not found in the response');
    }

    public function test_show_category_successfully(){
        $category = Categories::where('name', 'Ana Kategori')->first();
        $response = $this->getJson("api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'name',
                    'slug',
                    'parent_category_id',
                    'children'
                ]
            ]);
        $responseData = $response->json('data');
        $this->assertEquals('Ana Kategori', $responseData['name']);
        $this->assertStringContainsString('ana-kategori', $responseData['slug']);

        // Alt kategorilerin geldiğini doğrula
        $this->assertIsArray($responseData['children']);
        $this->assertGreaterThanOrEqual(1, count($responseData['children']));
    }

    //Kategori oluşturma test
    // Ana kategori oluşturma
    public function test_create_category_successfully(){
        $categoryData=[
            'name'=>"Bilgisayarlar",
            'slug' => 'bilgsayarlar-kategori-' . uniqid(),

        ];

        //API isteği yap

        $response=$this->postJson('api/v1/categories',$categoryData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'name',
                    'slug',
                    //'parent_category_id',
                    //'children'
                ]
            ]);
        //Veritabanında Doğrula
        $this->assertDatabaseHas('categories',[
            'name'=>"Bilgisayarlar",
            'slug' => $categoryData['slug'],
        ]);
    }

    //Alt Kategori oluşuyor mu?
    public function test_create_subcategory_successfully(){
        $categoryData = [
            'name' => 'Yeni Alt Kategori',
            'slug' => 'yeni-alt-kategori-' . uniqid(),
            'parent_category_id' => $this->parentCategory->id
        ];
        $response=$this->postJson('api/v1/categories',$categoryData);
        $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'slug',
                'parent_category_id'
            ]
        ]);

        $this->assertDatabaseHas('categories',[
            'name' => 'Yeni Alt Kategori',
            'slug' => $categoryData['slug'],
            'parent_category_id' => $this->parentCategory->id
        ]);
    }

    //kategori güncelleniyor mu
    public function test_update_category_successfully(){

        $updatedData=[
            'name'=>'Güncellenmiş Kategori',
            'slug' => 'güncellenmiş-kategori-' . uniqid(),
        ];

        $response=$this->putJson("api/v1/categories/{$this->parentCategory->id}",$updatedData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'name',
                    'slug',
                    'parent_category_id',
                ]
            ]);
        $responseData=$response->json('data');
        $this->assertEquals('Güncellenmiş Kategori', $responseData['name']);
        $this->assertEquals($updatedData['slug'], $responseData['slug']);

        $this->assertDatabaseHas('categories',[
            'id'=>$this->parentCategory->id,
            'name'=>'Güncellenmiş Kategori',
            'slug'=>$updatedData['slug']
        ]);
    }


    //Silme test
    public function test_delete_category_successfully(){
        $response=$this->deleteJson("api/v1/categories/{$this->childCategory->id}");
        $response->assertStatus(200)
            ->assertJson([
                'success'=>true,
                'message'=>__('messages.category_deleted')
            ]);

        //Silindi mi kontrol
        $this->assertDatabaseMissing('categories',[
            'id'=>$this->childCategory->id
        ]);
    }

    //Geçersiz Kategori verileri
    public function test_cannot_create_category_with_invalid_data(){
        $categoryData=[
            'slug'=>"gecersiz-kategori"
        ];
        $response=$this->postJson('api/v1/categories',$categoryData);
        $response->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'errors'
        ]);
    }

    //Var olmayan kategori id si ile güncelleme

    public function test_cannot_update_category_with_invalid_id()
    {
        // Var olmayan kategori ID'si
        $invalidId = 9999;

        // Güncelleme verileri
        $updateData = [
            'name' => 'Güncellenmiş Kategori',
            'slug' => 'guncellenmis-kategori'
        ];

        // API isteği yap
        $response = $this->putJson("/api/v1/categories/{$invalidId}", $updateData);

        // Yanıtı doğrula (hata bekleniyor)
        $response->assertStatus(404);
    }
    public function test_cannot_delete_category_with_invalid_id()
    {
        // Var olmayan kategori ID'si
        $invalidId = 9999;



        // API isteği yap
        $response = $this->deleteJson("/api/v1/categories/{$invalidId}");

        // Yanıtı doğrula (hata bekleniyor)
        $response->assertStatus(404);
    }

    public function test_unauthorized_user_cannot_create_category()
    {
        // Yetkisiz bir kullanıcı ile giriş yapmadan istek yap
        $user = User::factory()->create(); // Admin olmayan kullanıcı
        Sanctum::actingAs($user, ['*']);
        $categoryData = [
            'name' => 'Yetkisiz Kategori',
            'slug' => 'yetkisiz-kategori-' . uniqid(),
        ];

        $response = $this->postJson('api/v1/categories', $categoryData);

        // 403 Forbidden bekleniyor
        $response->assertStatus(403);
    }

}