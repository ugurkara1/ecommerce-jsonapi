<?php

namespace App\Services;

use App\Repositories\OrderRepository; // Import the correct namespace for OrderRepository
use Illuminate\Http\Request; // Import the correct namespace for Request
use Illuminate\Support\Facades\Validator; // Import the correct namespace for Validator
use App\Models\OrderProcess; // Import the correct namespace for OrderProcess
use App\Models\Order; // Import the correct namespace for Order

class OrderService{
    protected $orderRepository;
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function getAll()
    {
        return response()->json([
            'success' => true,
            'message' => __('messages.order_listed'),
            'data' =>$this->orderRepository->getAll()
        ], 200);
    }
    public function filterOrders()
    {
        $orders = $this->orderRepository->filterOrders();
        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.order_not_found')
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $orders
        ], 200);
    }
    public function create(Request $request){
        $validator=Validator::make($request->all(),[
            'customer_id'=>'required|exists:customers,id',
            'order_number' => 'required|string|unique:orders,order_number|regex:/^[0-9]{11}$/',
            "full_name"=>'required|string|max:255',
            "email"=>'required|email',
            "phone_number"=>'required|string|max:11',
            'currency_rate'=>'required|numeric',

        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data'),
                'errors'=>$validator->errors()
            ],422);
        }
        $data=$validator->validated();
        $data['process_id']=1;
        $data['current_status']=OrderProcess::find(1)->name ?? 'Sipariş Oluşturma';
        $data['subtotal']=0;
        $data['tax_amount']=0;
        $data['shipping_cost']=0;
        $data['total_amount']=0;

        $order=$this->orderRepository->create($data,$request->user());
        return response()->json([
            'success'=>true,
            'message'=>__('messages.category_created'),
            'data'=>$order
        ]);
    }
    public function update(Request $request,$id){
        $validator=Validator::make($request->all(),[
            'order_number' => 'required|string|unique:orders,order_number|regex:/^[0-9]{11}$/',
            'customer_id'=>'required|exists:customers,id',
            "full_name"=>'required|string|max:255',
            "email"=>'required|email',
            "phone_number"=>'required|string|max:11',
            'payment_id'=>'required|exists:payments,id',
            'campaign_id'=>'required|exists:campaigns,id',
            'order_date'=>'required|date',
            //'order_status'=>'required|in:pending,processing,shipped,delivered,canceled',
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
        $validated=$validator->validated();
        try{
            $order=Order::where('id',$id)->first();
            if(!$order){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.order_not_found')
                ],404);
            }
            $order=$this->orderRepository->update($validated,$id,$request->user());


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
        try{
            $order=Order::where('id',$id)->first();
            if(!$order){
                return response()->json([
                    'message'=>__('messages.order_not_found')
                ],404);
            }
            $order=$this->orderRepository->delete($id,$request->user());
            return response()->json([
                'success'=>true,
                'data'=>$order,
                'messages'=>__('messages.order_deleted')
            ],200);
        }catch(\Exception){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error'),
            ],500);
        }
    }
}
