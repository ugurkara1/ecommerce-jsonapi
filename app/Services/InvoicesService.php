<?php

namespace App\Services;

use App\Repositories\InvoicesRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Models\Invoices;
use App\Models\Order;
use App\Models\OrderAddress;

class InvoicesService{
    protected $invoicesRepository;
    public function __construct(InvoicesRepository $invoicesRepository){
        $this->invoicesRepository = $invoicesRepository;
    }
    public function getAll(){
        return response()->json([
            'success' => true,
            'message' => __('messages.invoice_listed'),
            'data' => $this->invoicesRepository->getAll()
        ],200);
    }
    public function show($id){
        $invoices=$this->invoicesRepository->show($id);
        if(!$invoices){
            return response()->json([
                'success' => false,
                'message' => __('messages.invoice_not_found'),
                'data' => null
            ],404);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.invoice_show'),
            'data' => $this->invoicesRepository->show($id)
        ],200);
    }
    public function create(Request $request){
        $validator=Validator::make($request->all(),[
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
        $validatedData=$validator->validated();
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

            //$invoices = Invoices::create($validatedData);
            $invoices=$this->invoicesRepository->create($validatedData, $request->user());

            Log::info('Invoice created successfully', ['invoice_id' => $invoices->id]);

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

    public function update(Request $request, $id){
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
            $user = $request->user();
            $invoice = $this->invoicesRepository->update($validatedData, $id, $user);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.invoice_not_found'),
                ], 404);
            }

            Log::info('Invoice updated successfully', ['invoice_id' => $invoice->id]);
            return response()->json([
                'success' => true,
                'message' => __('messages.invoice_updated'),
                'data' => $invoice
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating invoice', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    public function delete(Request $request,$id){
        try {
            $user = $request->user();
            $invoice = $this->invoicesRepository->delete($id, $user);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.invoice_not_found'),
                ], 404);
            }

            Log::info('Invoice deleted successfully', ['invoice_id' => $invoice->id]);
            return response()->json([
                'success' => true,
                'message' => __('messages.invoice_deleted'),
                'data' => $invoice
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting invoice', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }



}