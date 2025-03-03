<?php

namespace App\Http\Controllers;

use App\Models\Attributes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttributesController extends Controller
{
    //
    public function store(Request $request){
        $user=$request->user();
        if(!$user->hasPermissionTo("manage product attributes")){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.unauthorized')
            ],403);
        }
        //validasyon
        $validator=Validator::make($request->all(), [
            'name'=>'required|string|max:255|unique:attributes,name',
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.invalid_data'),
                'errors'=> $validator->errors()
            ],422);
        }
        $validated=$validator->validated();
        try{
            $attributes= Attributes::create([
                'name'=>$request->name,
            ]);
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($attributes)
                ->withProperties(['attributes'=>$validated])
                ->log("Function name: $functionName Attribute created successfully");
            return response()->json([
                'success'=>true,
                'message'=> __('messages.attribute_created'),
                'data'=> $attributes
            ],201);

        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error' ) . $e->getMessage()
            ],500);
        }
    }
    public function index(Request $request){
        try{
            $attributes=Attributes::all();
            return response()->json([
                'success'=>true,
                'message'=> __('messages.attribute_listed'),
                'data'=>$attributes
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error') . $e->getMessage()
            ],500);
        }
    }
    public function update(Request $request, $id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage product attributes')){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.unauthorized')
            ],403);
        }
        $validator=Validator::make($request->all(), [
            'name'=>'required|string|max:255|unique:attributes,name,'.$id,
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.invalid_data'),
                'errors'=> $validator->errors()
            ],422);
        }
        $validated=$validator->validated();
        try{
            $attributes=Attributes::where('id',$id)->first();
            $attributes->update($request->only('name'));
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($attributes)
                ->withProperties(['attributes'=>$validated])
                ->log("Function Name : $functionName Attributes updated successfully ");
            return response()->json([
                "success"=>true,
                "message"=> __('messages.attribute_updated'),
                'data'=>$attributes
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.service_error') . $e->getMessage()
            ],500);
        }
    }
    public function destroy(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo("manage product attributes")){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.unauthorized')
            ],403);
        }
        try{
            $attributes=Attributes::where('id',$id)->first();
            if($attributes->values()->exists()){
                return response()->json([
                    'success'=>false,
                    'message'=> __('messages.attribute_has_values')
                ],422);
            }
            $attributesData=[
                'id'=>$attributes->id,
                'name'=>$attributes->name,

            ];
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($attributes)
                ->withProperties(['attributes'=>$attributesData])
                ->log("Function name: $functionName Attribute deleted successfully");
            $attributes->delete();
            return response()->json([
                'success'=> true,
                'data'=> $attributes,
                'messages'=>__('messages.attribute_deleted')
            ],200);
        }catch(\Exception $e){

            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error') .  $e->getMessage()
            ],500);
        }
    }

}