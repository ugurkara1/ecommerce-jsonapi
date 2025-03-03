<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Attributes;
use App\Models\AttributeValues;

class AttributeValuesController extends Controller
{
    //attributeValues add
    public function store(Request $request, Attributes $attribute)
    {
        $user=$request->user();
        if(!$user->hasPermissionTo('manage product attributes')){
            return response()->json([
                'success'=>false,
                'message'=>'Unauthorized'
            ],401);
        }
        $validator = Validator::make($request->all(), [
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_values')->where(function ($query) use ($attribute) {
                    return $query->where('attribute_id', $attribute->id);
                })
            ]
        ]);
        if( $validator->fails() ){
            return response()->json([
                'success'=>false,
                'message'=> 'messages.invalid_data',
                'errors'=> $validator->errors()
            ],422);
        }
        try{
            $value=$attribute->values()->create($request->all());
            return response()->json([
                'success'=>true,
                'message'=>__('messages.attribute_value_created'),
                'data'=>$value
            ],201);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error') . $e->getMessage()
            ], 500);
        }
    }
    public function index(Attributes $attribute)
    {
        try{
            $values=$attribute->values()
                ->orderBy('value')
                ->get();
            return response()->json([
                'success'=>true,
                'message'=>__('messages.attribute_value_listed'),
                'data'=>$values
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error') . $e->getMessage()
            ], 500);
        }
    }
    public function show(Attributes $attribute,AttributeValues $value){
        try{
            if($value->attribute_id !== $attribute->id){
                return response()->json([
                    'success'=>false,
                    'message'=> __('messages.invalid_relationship')
                ] ,400);
            }
            return response()->json([
                'success'=>true,
                'data'=>$value->load('attribute')
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error') . $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, Attributes $attribute, AttributeValues $value)
    {
        $user = $request->user();
        // Yetki kontrolünü diğer metotlarla uyumlu hale getiriyoruz:
        if (!$user->hasPermissionTo('manage product attributes')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_values')->where(function ($query) use ($attribute) {
                    return $query->where('attribute_id', $attribute->id);
                })->ignore($value->id)
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message'=>__('messages.invalid_data'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Güncelleme, doğru model üzerinden yapılmalı:
            $value->update($request->all());
            return response()->json([
                'success' => true,
                'message'=> __('messages.attribute_value_updated'),
                'data' => $value
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => __('messages.service_error') . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request,Attributes $attribute, AttributeValues $value)
    {
        $user=$request->user();
        if(!$user->hasPermissionTo('manage product attributes')){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.unauthorized')
            ],401);
        }
        try{
            if($value->variants()->exists()){
                return response()->json([
                    'success'=>false,
                    'message' => __('messages.value_has_variants')
                ],422);
            }
            $value->delete();
            return response()->json([
                'success'=>true,
                'message' => __('messages.value_deleted')
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error') . $e->getMessage()
            ], 500);
        }
    }
}
