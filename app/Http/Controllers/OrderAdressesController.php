<?php

namespace App\Http\Controllers;

use App\Models\OrderAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderAdressesController extends Controller
{
    //
    public function index(){
        $orderAddress=OrderAddress::with('orders')->get();
        return response()->json([
            'success'=>true,
            'message'=>__('messages.orderAddress_listed'),
            'data'=>$orderAddress
        ]);
    }
    public function show($id){
        $orderAddress=OrderAddress::with('orders')->findOrFail($id);
        return response()->json([
            'success'=>true,
            'message'=>__('messages.orderAddress_showed'),
            'data'=>$orderAddress
        ]);
    }
    public function store(Request $request){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ],403);
        }
        $validator=Validator::make($request->all(),[
            "order_id"=>'required|exists:orders,id',
            "address_type"=>'required|string|in:Shipping,Billing',
            "company_name"=>'nullable|string',
            "recipient_name"=>'required|string|max:255',
            "street"=>'required|string|max:255',
            "city"=>'required|string|max:255',
            "district"=>'required|string|max:255',
            "postal_code"=>'required|string|max:20',
            "country"=>'required|string|max:255',
            "phone"=>'required|string|max:14',

        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data')
            ],422);
        }
        $data=$validator->validated();
        $orderAddresses=OrderAddress::create($data);


        $functionName=__FUNCTION__;
        activity()
            ->causedBy($user)
            ->performedOn($orderAddresses)
            ->withProperties(['attributes'=>$data])
            ->log("Function Name: $functionName. OrderAddress created successfully");

        return response()->json([
            'succes'=>true,
            'message'=>__('messages.created_orderAddresses'),
            'data'=>$orderAddresses
        ],201);
    }

    public function update(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'message'=>__('messages.unauthorized')
            ],403);
        }
        $validator=Validator::make($request->all(),[
            'order_id'=>'required|exists:orders,id',
            'address_type'=>'required|string|in:Shipping,Billing',
            'company_name'=>'nullable|string',
            'recipient_name'=>'required|string|max:255',
            'street'=>'required|string|max:255',
            'city'=>'required|string|max:50',
            'district'=>'required|string|max:255',
            'postal_code'=>'required|string|max:20',
            'country'=>'required|string|max:55',
            'phone'=>'required|string|max:14',

        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
            ],422);
        }
        $data=$validator->validated();
        try{
            $orderAddresses=OrderAddress::where('id',$id)->first();

            if(!$orderAddresses){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.orderAddress_not_found')
                ],404);
            }
            $orderAddressData=[
                'id'=>$orderAddresses->id,
                'address_type'=>$orderAddresses->address_type,
                'company_name'=>$orderAddresses->company_name,
                'recipient_name'=>$orderAddresses->recipient_name,
                'street'=>$orderAddresses->street,
                'city'=>$orderAddresses->city,
                'district'=>$orderAddresses->district,
                'postal_code'=>$orderAddresses->postal_code,
                'country'=>$orderAddresses->country,
                'phone'=>$orderAddresses->phone,

            ];
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($orderAddresses)
                ->withProperties(['attributes'=>$orderAddressData])
                ->log("Function Name: $functionName . OrderAddress updated");

            $orderAddresses->update($data);
            return response()->json([
                'success'=>true,
                'message'=>__('messages.orderAddress_updated'),
                'data'=>$orderAddresses
            ],200);

        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error'),
                'errors'  => $e->getMessage()
            ],500);
        }
    }

    public function destroy(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ],403);
        }
        try{
            $orderAddress=OrderAddress::where('id',$id)->first();
            if(!$orderAddress){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.orderAddress_not_found'),
                ],404);
            }
            $orderAddressData=[
                'id'=>$orderAddress->id,
                'address_type'=>$orderAddress->address_type,
                'company_name'=>$orderAddress->company_name,
                'recipient_name'=>$orderAddress->recipient_name,
                'street'=>$orderAddress->street,
                'city'=>$orderAddress->city,
                'district'=>$orderAddress->district,
                'postal_code'=>$orderAddress->postal_code,
                'country'=>$orderAddress->country,
                'phone'=>$orderAddress->phone,

            ];
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($orderAddress)
                ->withProperties(['attributes'=>$orderAddressData])
                ->log("Function Name: $functionName.OrderAddress deleted successfully.");

            $orderAddress->delete();
            return response()->json([
                'success'=>true,
                'data'=>$orderAddress,
                'messages'=>__('messages.order_deleted')
            ]);

        } catch(\Exception $e) {
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ]);
        }
    }

}