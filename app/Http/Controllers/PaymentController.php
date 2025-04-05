<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;

class PaymentController extends Controller
{
    //
    public function __construct(){
        // Yalnızca ödeme oluşturma, güncelleme, silme işlemleri için "super admin", "admin", "order manager" yetkisi
        $this->middleware('role:super admin|admin|order manager')->only(['store', 'update', 'destroy']);
        // Listeleme ve detay görüntüleme için müşteriler de dahil
        $this->middleware('role:super admin|admin|order manager|customer')->only(['index', 'show']);
    }
    public function index(){
        $payments=Payment::with('customer')->get();
        return response()->json([
            'success'=>true,
            'message'=>__('messages.payment_listed'),
            'data'=>$payments
        ]);
    }

    public function show(Request $request,$id){

        $user=$request->user();

        $payments=Payment::with('customer')->findOrFail($id);
        return response()->json([
            'success'=>true,
            'message'=>__('messages.payment_showed'),
            'data'=>$payments
        ]);

    }
    public function store(Request $request){

        //$customerId=$request->user()->id;


        $validator=Validator::make($request->all(), [
            //'customer_id'=>'required|exists:customers,id',
            'order_id' => 'required|exists:orders,id',
            'payment_method'=> 'sometimes|required|in:credit_cart,paypal,cash_on_delivery',
            //'amount'=> 'required|numeric',
            //'payment_status'=> 'required|in:pending,completed,failed,renfunded',
            'payment_date'=> 'required|date',
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data'),
                'errors'=>$validator->errors()
            ]);
        }

        $data=$validator->validated();
        $order=Order::find($data['order_id']);
        /*
        $currentProcess=$order->orderProcesses()->latest()->first();
        if(!$currentProcess || $currentProcess->status!="Ödeme İşlemi"){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.only_allowed_in_creation_phase')
            ],403);
        }
        */
        $currentProcess=$order->process;
        if ($currentProcess && $currentProcess->name != "Payment Process") {
            return response()->json([
                'success' => false,
                'message' => __('messages.only_allowed_in_creation_phase')
            ], 403);
        }
        $data['amount']=$order->total_amount;
        $data['customer_id'] = $order->customer_id;

        /*
        $order->orderProcesses()->update([
            'status'=>'Sipariş Onayı',
            'description'=>'Siparişiniz onaylandı'
        ]);
        */
        $payment=Payment::create($data);
        $order->update([
            'payment_id'=>$payment->id,
        ]);
        activity()
            ->causedBy($request->user())
            ->performedOn($payment)
            ->withProperties(['payment'=>$data])
            ->log('Payment created successfully');
        return response()->json([
            'success'=>true,
            'message'=>__('messages.payment_successfully'),
            'data'=>$payment
        ],200);

    }



    public function update(Request $request, $id){
        $user=$request->user();

        $validator=Validator::make($request->all(), [
            'customer_id'=> 'required|exists:customers,id',
            'payment_method'=> 'sometimes|required|in:credit_cart,paypal,cash_on_delivery',
            'amount'=> 'required|numeric',
            'payment_status'=> 'required|in:pending,completed,failed,renfunded',
            'payment_date'=> 'required|date',
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
            $payment=Payment::where('id',$id)->first();
            if(!$payment){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.payment_not_found'),
                ],404);
            }
            $payment->update($validated);
            activity()
                ->causedBy($user)
                ->performedOn($payment)
                ->withProperties(['payment'=>$validated])
                ->log('Payment updated successfully');
            return response()->json([
                'success'=>true,
                'message'=>__('messages.payment_updated'),
                'data'=>$payment
            ],200);



        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_occurred'),
                'errors'  => $e->getMessage()
            ], 500);
        }

    }

    public function destroy(Request $request, $id){

        $user=$request->user();

        try{
            $payment=Payment::where('id',$id)->first();
            if(!$payment){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.payment_not_found'),
                ],404);
            }
            $paymentData=[
                'id'=>$payment->id,
                'customer_id'=>$payment->customer_id,
                'amount'=>$payment->amount,
                'payment_status'=>$payment->payment_status,
                'payment_date'=>$payment->payment_date,
            ];
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($payment)
                ->withProperties(['attributes'=>$paymentData])
                ->log("Function Name: $functionName.Brands deleted successfully.");
            $payment->delete();
            return response()->json([
                'success'=>true,
                'data'=>$payment,
                'messages'=>__('messages.payment_deleted'),
            ],200);

        }catch (\Exception $e) {
            return response()->json([
                'success'=>false,
                'data'=>$payment,
                'message'=>__('messages.service_error') . $e->getMessage()
            ]);
        }
    }
}