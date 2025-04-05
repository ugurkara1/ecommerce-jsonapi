<?php

namespace Tests\Unit;
use App\Models\Attributes;
use App\Models\User;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AttributesTest extends TestCase{
    protected function setUp(): void
    {
        parent::setUp();

        // Test kullanıcısı oluşturma ve rol verme (spatie/laravel-permission kullanıldığını varsayıyoruz)
        $superadmin = User::factory()->create();
        $superadmin->assignRole('super admin');
        Sanctum::actingAs($superadmin, ['*']);

        //Test Attribute oluştur

        Attributes::factory()->create([
            'name' => 'Test Attribute'
        ]);
    }

    //attribute listesi test
    //Başarılı bir şekilde tüm attribute'leri listeleme
    public function test_get_all_attributes_successfully(){
        $response=$this->getJson('api/v1/attributes');

        //Yanıtı Doğrula
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    '*'=>[
                        'id',
                        'name'
                    ]
                ]
            ]);

            $responseData=$response->json('data');
            $this->assertEquals('Test Attribute',$responseData[0]['name']);
    }

    //id parametresi ile filtreleme
    public function test_show_attribute_successfully(){
        $attribute=Attributes::where('name','Test Attribute')->first();
        $response=$this->getJson('api/v1/attributes/'.$attribute->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'name'
                ]
            ]);

            $responseData=$response->json('data');
            $this->assertEquals('Test Attribute',$responseData['name']);

    }
    //Var olmayan id ile gösterme test
    public function test_show_non_existent_attributes(){
        $nonExistendId=9999;

        $response=$this->getJson('api/v1/attributes/'.$nonExistendId);
        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }

    //Yeni attribute oluşturma test
    public function test_create_attribute_successfully(){
        $attributeData=[
            'name'=>'Yeni Attribute'
        ];

        //API isteği yap
        $response=$this->postJson('api/v1/attributes',$attributeData);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'name'
                ]
            ]);
        // Veritabanında doğrula
        $this->assertDatabaseHas('attributes', [
            'name' => 'Yeni Attribute'
        ]);


    }

    //Geçersiz veri ile test et
    //Validasyonu service katmanında bir metotla yaptığım için update,delete methodunda tekrardan test etmiyorum
    public function test_create_attribute_with_invalid_data(){
        $attributeData=[
            'name'=>''
        ];

        //API isteği yap
        $response=$this->postJson('api/v1/attributes',$attributeData);
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }

    //Attribute güncelleme test
    public function test_update_attribute_successfully(){
        $attribute=Attributes::where('name','Test Attribute')->first();
        $updateData=[
            'name'=>'Güncellenmiş Attribute'
        ];

        $response=$this->putJson('api/v1/attributes/'.$attribute->id,$updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'name'
                ]
            ]);

            $responseData=$response->json('data');
            $this->assertEquals('Güncellenmiş Attribute',$responseData['name']);

            //Veri tabanında doğrula
            $this->assertDatabaseHas('attributes', [
                'id' => $attribute->id,
                'name' => 'Güncellenmiş Attribute'
            ]);
    }

    //Var olmayan id ile güncelleme test
    public function test_update_non_existent_id_attribute(){
        $nonExistentId=9999;
        $updateData=[
            'name'=>'Güncellenmiş Attribute'
        ];

        $response=$this->putJson('api/v1/attributes/'.$nonExistentId,$updateData);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }

    //attribute silme test
    public function test_delete_attribute_successfully()
    {
        // İlişkili değerleri olmayan benzersiz bir attribute oluştur
        $attributeToDelete = Attributes::factory()->create([
            'name' => 'Delete Test Attribute ' . uniqid()
        ]);

        // AttributeValues oluşturmuyoruz!

        $response = $this->deleteJson('api/v1/attributes/'.$attributeToDelete->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);
    }
    //Geçersiz id ile silme test
    public function test_delete_non_existent_id_attribute(){
        $nonExistentId=9999;
        $response=$this->deleteJson('api/v1/attributes/'.$nonExistentId);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message'
            ]);

    }

    //Var olan markayla yeni bir attribute oluşturma test
    public function test_cannot_create_duplicate_attribute_name(){
        $duplicateData=[
            'name'=>'Test Attribute'
        ];

        $response=$this->postJson('api/v1/attributes',$duplicateData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }

    //Attribute value sahipse silinemez
    public function test_cannot_delete_attribute_with_values(){
        $attribute=Attributes::where('name','Test Attribute')->first();
        $attribute->values()->create(['value' => 'Test Value']);

        $response=$this->deleteJson('api/v1/attributes/'.$attribute->id);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }
}