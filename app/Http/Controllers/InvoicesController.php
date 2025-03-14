<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\Invoices;
use App\Models\Order;
use App\Models\OrderAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicesController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        if (!$user->hasPermissionTo('manage orders')) {
            Log::warning('Unauthorized invoice listing attempt', ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ], 403);
        }
        $invoices=Invoices::with('order','customer')->get();
        return response()->json([
            'success'=>false,
            'message'=>__('messages.invoices_listed'),
            'data'=>$invoices
        ]);
    }

    public function show(Request $request,$id){
        $user = $request->user();
        if (!$user->hasPermissionTo('manage orders')) {
            Log::warning('Unauthorized invoice listing attempt', ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ], 403);
        }
        $invoices=Invoices::with('order','customer')->findOrFail($id);
        return response()->json([
            'success'=>true,
            'message'=>__('messages.orderAddress_showed'),
            'data'=>$invoices
        ]);
    }

/*
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage orders')) {
            Log::warning('Unauthorized access attempt to create invoice', ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ]);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number|regex:/^[0-9]{11}$/',
            'invoice_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            Log::error('Invoice validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_data'),
                'errors' => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $order = Order::findOrFail($validatedData['order_id']);
            Log::info('Order retrieved successfully', ['order_id' => $order->id]);

            $validatedData['customer_id'] = $order->customer_id;

            // OrderAddress'ı order_id'ye göre alıyoruz
            $orderAddress = OrderAddress::where('order_id', $validatedData['order_id'])->firstOrFail();
            Log::info('OrderAddress retrieved successfully', ['order_id' => $validatedData['order_id']]);

            // Billing address'ı belirleme
            if ($orderAddress->address_type === 'Shipping') {
                $validatedData['billing_address'] = $orderAddress->address_type;
            }
            $validatedData['billing_address'] = $orderAddress->address_type;
            $validatedData['tax_amount'] = $order->tax_amount;
            $validatedData['total_amount'] = $order->total_amount;
            $validatedData['currency'] = $order->currency_code;

            $invoices = Invoices::create($validatedData);
            Log::info('Invoice created successfully', ['invoice_id' => $invoices->id]);

            $functionName = __FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($invoices)
                ->withProperties($validatedData)
                ->log("Function name: $functionName. Fatura oluşturuldu.");

            return response()->json([
                'success' => true,
                'message' => __('messages.invoices_created'),
                'data' => $invoices
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating invoice', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }
*/
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage orders')) {
            Log::warning('Unauthorized invoice update attempt', ['user_id' => $user->id]);
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'invoice_number' => 'required|string|unique:invoices,invoice_number,' . $id . '|regex:/^[0-9]{11}$/',
            'invoice_date'   => 'required|date',
        ]);

        if ($validator->fails()) {
            Log::error('Invoice update validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_data'),
                'errors'  => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();

        try {
            $invoice = Invoices::where('id',$id)->first();
            $invoice->update($validatedData);
            Log::info('Invoice updated successfully', ['invoice_id' => $id]);

            activity()
                ->causedBy($user)
                ->performedOn($invoice)
                ->withProperties($validatedData)
                ->log("Invoice updated successfully via admin panel.");

            return response()->json([
                'success' => true,
                'message' => __('messages.invoice_updated'),
                'data'    => $invoice
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating invoice', ['invoice_id' => $id, 'error' => $e->getMessage()]);
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
            ]);
        }
        try{
            $invoices=Invoices::where('id',$id)->first();
            if(!$invoices){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.invoices_not_found')
                ],404);
            }
            $invoicesData=[
                'id'=>$invoices->id,
                'customer_id'=>$invoices->customer_id,
                'invoice_number'=>$invoices->invoice_number,
                'invoice_date'=>$invoices->invoice_date,
                'billing_address'=>$invoices->billing_address,
                'tax_amount'=>$invoices->tax_amount,
                'total_amount'=>$invoices->total_amount,
                'currency'=>$invoices->currency,
            ];
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($invoices)
                ->withProperties(['attributes'=>$invoicesData])
                ->log("Function name: $functionName. Invoices deleted");

            $invoices->delete();
            return response()->json([
                'success'=>true,
                'data'=>$invoices,
                'messages'=>__('messages.invoices_deleted')
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'messages'=>__('messages.service_errors'),
                'errors'=>$e->getMessage()
            ]);
        }

    }

    //Sipariş faturasını mail olarak göndermek için
    public function sendInvoiceEmail(Request $request, $id)
    {
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ]);
        }
        try {
            // İlişkileri yüklüyoruz
            $invoice = Invoices::with([
                'customer',
                'order.orderProducts.variant.product' // Ürün bilgilerini getir
            ])->findOrFail($id);

            // PDF oluşturma
            $pdf = Pdf::loadView('invoice', compact('invoice'));
            //resources/views/invoice.blade.php
            $pdfPath = storage_path("app/public/invoices/{$invoice->invoice_number}.pdf");
            $pdf->save($pdfPath);

            // E-posta gönderimi
            Mail::to($invoice->customer->email)->send(new InvoiceMail($invoice, $pdfPath));

            return response()->json([
                'success' => true,
                'message' => 'Fatura başarıyla gönderildi'
            ]);

        } catch (\Exception $e) {
            Log::error('Fatura gönderim hatası: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Fatura gönderilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}