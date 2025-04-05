<?php

namespace App\Services;

use App\Contracts\OrderAddressesContract;
use App\Models\OrderAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\OrderProcess;

class OrderAddressesService{
    protected $orderAddressRepository;

    public function __construct(OrderAddressesContract $orderAddressRepository){
        $this->orderAddressRepository=$orderAddressRepository;

    }
    public function getAll(){
        return response()->json([
            'success' => true,
            'message' => __('messages.orderAddress_listed'),
            'data' => $this->orderAddressRepository->getAll()
        ], 200);
    }
    public function show($id){
        $address=$this->orderAddressRepository->show($id);
        if(!$address){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.orderAddress_not_found'),
                'data'=>null
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.orderAddress_showed'),
            'data' => $this->orderAddressRepository->show($id),
        ], 200);
    }

    public function create(Request $request){
        $validator=Validator::make($request->all(),[
            "order_id" => 'required|exists:orders,id',
            "address_type" => 'required|string|in:Shipping,Billing',
            "company_name" => 'nullable|string',
            "recipient_name" => 'required|string|max:255',
            "street" => 'required|string|max:255',
            "city" => 'required|string|max:255',
            "district" => 'required|string|max:255',
            "postal_code" => 'required|string|max:20',
            "country" => 'required|string|max:255',
            "phone" => 'required|string|max:14',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_data')
            ], 422);
        }
        $data = $validator->validated();
        $order = Order::find($data['order_id']);
        $currentProcess = $order->process;
        if ($currentProcess && $currentProcess->name != "Order Creation") {
            return response()->json([
                'success' => false,
                'message' => __('messages.only_allowed_in_creation_phase')
            ], 403);
        }
        $orderAddresses =$this->orderAddressRepository->create($data,$request->user());
        $orderProcess = OrderProcess::find(2);

        $order->update([
            'process_id' => 2,
            'current_status' => $orderProcess->name
        ]);

        $orderAddresses=$this->orderAddressRepository->create($data,$request->user());
        return response()->json([
            'success' => true,
            'message' => __('messages.created_orderAddresses'),
            'data' => $orderAddresses
        ], 201);
    }
    public function update(Request $request,$id){
        $validator=Validator::make($request->all(),[
            'order_id' => 'required|exists:orders,id',
            'address_type' => 'required|string|in:Shipping,Billing',
            'company_name' => 'nullable|string',
            'recipient_name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:50',
            'district' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:55',
            'phone' => 'required|string|max:14',
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data'),

            ],422);
        }
        $data=$validator->validated();

        try{
            $address=$this->orderAddressRepository->update($data,$id,$request->user());
            if(!$address){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.orderAddress_not_found')
                ],404);
            }
            Log::info('Order address updated successfully',['order_address_id'=>$address->id]);
            return response()->json([
                'success'=>true,
                'message'=>__('messages.orderAddress_updated'),
                'data'=>$address
            ],200);
        }catch(\Exception $e){
            Log::error('Error creating invoice', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('messages.service_error'),
                'errors' => $e->getMessage()
            ], 500);
        }

    }
    public function destroy(Request $request,$id){
        try{
            $user=$request->user();
            $address=$this->orderAddressRepository->destroy($id,$request->user());
            if(!$address){
                return response()->json([
                    'success' => false,
                    'message' => __('messages.orderAddress_not_found')
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => __('messages.order_deleted'),
                'data' => $address
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error'),
                'errors' => $e->getMessage()
            ], 500);

        }
    }
}