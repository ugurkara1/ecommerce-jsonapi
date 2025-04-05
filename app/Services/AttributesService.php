<?php

namespace App\Services;
use App\Contracts\AttributesContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class AttributesService{
    protected $attributesRepository;
    public function __construct(AttributesContract $attributesRepository){
        $this->attributesRepository = $attributesRepository;

    }
    public function getAll(){
        return response()->json([
            'success'=>true,
            "message"=>__('messages.attributes_listed'),
            'data'=>$this->attributesRepository->getAll()
        ]);
    }
    public function show($id){
        $attributes=$this->attributesRepository->show($id);
        if(!$attributes){
            return response()->json([
                'success'=>false,
                "message"=>__('messages.attribute_not_found')
            ],404);
        }
        return response()->json([
            'success'=>true,
            "message"=>__('messages.attribute_show'),
            'data'=>$this->attributesRepository->show($id)
        ],200);
    }
    public function create(Request $request){
        $validated=$this->validateRequest($request);
        if($validated instanceof JsonResponse){
            return $validated;
        }
        $attributes=$this->attributesRepository->create($validated,$request->user());
        return response()->json([
            'success'=>true,
            "message"=>__('messages.attribute_created'),
            'data'=>$attributes
        ],201);
    }
    public function update(Request $request, $id) {
        // Önce kaydın var olup olmadığını kontrol et
        $attribute = $this->attributesRepository->show($id);
        if (!$attribute) {
            return response()->json([
                'success' => false,
                'message' => __('messages.attribute_not_found')
            ], 404);
        }

        // Sonra validasyon yap
        $validated = $this->validateRequest($request, $id);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        // Kayıt varsa ve validasyon geçtiyse güncelleme yap
        $attributes = $this->attributesRepository->update($id, $validated, $request->user());

        return response()->json([
            'success' => true,
            'message' => __('messages.attribute_updated'),
            'data' => $attributes
        ], 200);
    }
    public function delete(Request $request, $id) {
        // AttributesRepository'den dönen yanıtı al
        $result = $this->attributesRepository->delete($id, $request->user());

        // Eğer result bir JsonResponse ise (hata durumunda)
        if ($result instanceof JsonResponse || (is_object($result) && method_exists($result, 'getStatusCode'))) {
            return $result; // Hata yanıtını doğrudan dön
        }

        // Normal başarılı yanıt
        return response()->json([
            'success' => true,
            "message" => __('messages.attribute_deleted'),
            'data' => $result
        ],200);
    }
    public function validateRequest(Request $request,$id=null){
        $rules=[
            'name' => 'required|string|max:255|unique:attributes,name' . ($id ? ',' . $id : '')        ];
        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data'),
                'errors'=>$validator->errors()
            ],422);
        }
        return $request->validate($rules);
    }

}
