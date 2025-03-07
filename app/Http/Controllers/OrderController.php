<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    //

    public function store(Request $request){
        $customer_Id=$request->user()->id;

        $user=$request->user();
        $validator=Validator::make($request->all(),[
            //'customer_id'=>'required|exists:customers,id',
            "full_name"=>'required|string|max:255',
            "email"=>'required|email',
            "phone_number"=>'required|string|max:11',
            'payment_id'=>'required|exists:payments,id',
            'campaign_id'=>'required|exists:campaigns,id',
            'order_date'=>'required|date',
            'order_status'=>'required|in:pending,processing,shipped,delivered,canceled',
            'currency_code'=>'required|in:USD,EUR,GBP,TRY,JPY,AUD,CAD',
            'currency_rate'=>'required|numeric',
            'subtotal'=>'required|numeric',
            'tax_amount'=>'required|numeric',
            'shipping_cost'=>'required|numeric',
            'total_amount'=>'required|numeric',
            'shipping_tracking_number'=>'nullable|string',
            'shipment_date'=>'nullable|date',
            'delivery_date'=>'nullable|date'
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data'),
                'errors'=>$validator->errors()
            ],422);
        }
        $data=$validator->validated();
        $data['customer_id']=$customer_Id;
        $order=Order::create($data);

        $functionName=__FUNCTION__;
        activity()
            ->causedBy($user)
            ->performedOn($order)
            ->withProperties($data)
            ->log("Function Name : $functionName. Order created successfully");
        return response()->json([
            'success'=>true,
            'message'=>__('messages.created_order'),
            'data'=>$order
        ],201);

    }

    public function update(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
            ],403);
        }

        $validator=Validator::make($request->all(),[
            'customer_id'=>'required|exists:customers,id',
            "full_name"=>'required|string|max:255',
            "email"=>'required|email',
            "phone_number"=>'required|string|max:11',
            'payment_id'=>'required|exists:payments,id',
            'campaign_id'=>'required|exists:campaigns,id',
            'order_date'=>'required|date',
            'order_status'=>'required|in:pending,processing,shipped,delivered,canceled',
            'currency_code'=>'required|in:USD,EUR,GBP,TRY,JPY,AUD,CAD',
            'currency_rate'=>'required|numeric',
            'subtotal'=>'required|numeric',
            'tax_amount'=>'required|numeric',
            'shipping_cost'=>'required|numeric',
            'total_amount'=>'required|numeric',
            'shipping_tracking_number'=>'nullable|string',
            'shipment_date'=>'nullable|date',
            'delivery_date'=>'nullable|date'
        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data'),
                'errors'=>$validator->errors()
            ],404);
        }
        $validated=$validator->validated();
        try{
            $order=Order::where('id',$id)->first();
            if(!$order){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.order_not_found')
                ],404);
            }
            $order->update($validated);

            $functionName = __FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($order)
                ->withProperties(['attributes' => $validated]) // Güncellenen veriyi kaydet
                ->log("Function Name: $functionName. Order updated");

            return response()->json([
                'success'=>true,
                'message'=>__('messages.order_updated'),
                'data'=>$order
            ],200);

        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error'),
                'errors'  => $e->getMessage()
            ], 500);
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
            $order=Order::where('id',$id)->first();
            if(!$order){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.order_not_found')
                ],404);
            }
            $orderData=[
                'id'=>$order->id,
                'customer_id'=>$order->customer_id,
                'payment_id'=>$order->payment_id,
                'campaign_id'=>$order->campaign_id,
                'full_name'=>$order->full_name,
                'email'=>$order->email,
                'phone_number'=>$order->phone_number,
                'order_date'=>$order->order_date,
                'order_status'=>$order->order_status,
                "currency_code"=>$order->currency_code,
                "currency_rate"=>$order->currency_rate,
                "subtotal"=>$order->subtotal,
                "tax_amount"=>$order->tax_amount,
                "shipping_tracking_number"=>$order->shipping_tracking_number,
                "shipment_date"=>$order->shipment_date,
                "delivery_date"=>$order->delivery_date
            ];
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($order)
                ->withProperties(['attributes'=>$orderData])
                ->log("Function Name: $functionName.Order deleted successfully.");

            $order->delete();
            return response()->json([
                'success'=>true,
                'data'=>$order,
                'messages'=>__('messages.order_deleted')
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error'),
            ]);
        }
    }
}