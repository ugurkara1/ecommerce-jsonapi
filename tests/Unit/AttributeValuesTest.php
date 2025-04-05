<?php


namespace Tests\Unit;

use App\Models\AttributeValues;
use App\Models\Attributes;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AttributeValuesTest extends TestCase{

    protected $admin;
    protected $attributes;
    protected $attributeValue;
    protected function setUp(): void
    {
        parent::setUp();

        $superadmin = User::factory()->create();
        $superadmin->assignRole('super admin');
        Sanctum::actingAs($superadmin, ['*']);

        $this->attributes = Attributes::factory()->create([
            'name' => 'Test Attribute'
        ]);
        $this->attributeValue=AttributeValues::factory()->create([
            'value' => 'Test Attribute Value',
            'attribute_id' => $this->attributes->id
        ]);


    }
    //attribute listesi test
    public function test_get_all_attribute_values_successfully(){
        $response=$this->getJson('api/v1/attribute-values');

        //Yanıtı Doğrula
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    '*'=>[
                        'id',
                        'value',
                        'attribute_id'
                    ]
                ]
            ]);

            $responseData=$response->json('data');
            $this->assertEquals('Test Attribute Value',$responseData[0]['value']);
    }

    //id parametresi ile filtreleme
    public function test_show_attribute_values_successfully(){
        $attributeValue=AttributeValues::where('value','Test Attribute Value')->first();
        $response=$this->getJson('api/v1/attribute-values/'.$attributeValue->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'value',
                    'attribute_id'
                ]
            ]);

            $responseData=$response->json('data');
            $this->assertEquals('Test Attribute Value',$responseData['value']);
    }

    //attribute value ekleme test
    public function test_create_attribute_values_successfully(){
        $attributeValueData=[
            'value'=> 'Test Attribute Value 2',
            'attribute_id'=> $this->attributes->id
        ];

        $response=$this->postJson("api/v1/attribute-values/{$this->attributes->id}",$attributeValueData);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'value',
                    'attribute_id'
                ]
            ]);
        $responseData=$response->json('data');
        $this->assertEquals('Test Attribute Value 2',$responseData['value']);
        $this->assertDatabaseHas('attribute_values',[
            'value'=> $attributeValueData['value'],
            'attribute_id'=> $this->attributes->id
        ]);
    }

    //Geçersiz veri ile attribute value ekleme test
    public function test_create_attribute_values_with_invalid_data(){
        $attributeValueData=[
            'value'=> '',
        ];

        $reponse=$this->postJson("api/v1/attribute-values/{$this->attributes->id}",$attributeValueData);
        $reponse->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message'
            ]);

    }
    //attribute value güncelleme test
    public function test_update_attribute_successfully(){
        $attributeValue=AttributeValues::where('value','Test Attribute Value')->first();
        $updateData=[
            'value'=>'Güncellenmiş Attribute Value'
        ];
        $response=$this->putJson('api/v1/attribute-values/'.$attributeValue->id,$updateData);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'=>[
                    'id',
                    'value',
                    'attribute_id'
                ]
            ]);

            $responseData=$response->json('data');
            $this->assertEquals('Güncellenmiş Attribute Value',$responseData['value']);
            $this->assertDatabaseHas('attribute_values',[
                'id'=>$attributeValue->id,
                'value'=>'Güncellenmiş Attribute Value'
            ]);
    }

    //var olmayan id ile güncelleme test
    public function test_update_non_existent_id_attribute_value(){
        $nonExistentId=9999;
        $updateData=[
            'value'=>'Güncellenmiş Attribute Value'

        ];
        $reponse=$this->putJson('api/v1/attribute-values/'.$nonExistentId,$updateData);
        $reponse->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }
    //attribute value silme test
    public function test_delete_attribute_value_successfully(){
        $response=$this->deleteJson('api/v1/attribute-values/'.$this->attributeValue->id);
        $response->assertStatus(200)
            ->assertJson([
                'success'=>true,
                'message'=>__('messages.attribute_values_deleted')
            ]);

            //Veri tabanında silindi mi kontrol et
            $this->assertDatabaseMissing('attribute_values',[
                'id'=>$this->attributeValue->id
            ]);
    }

    //var olmayan id ile silme test
    public function test_delete_non_existent_id_attribute_value(){
        $nonExistentId=9999;
        $response=$this->deleteJson('api/v1/attribute-values/'.$nonExistentId);
        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }


}
