<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Regions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Campaigns;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderDeliveredMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\InvoiceMail;
use App\Mail\OrderPreparedMail;
use App\Models\Invoices;
use App\Models\OrderAddress;
use App\Models\ProductVariantRegion;
use App\Models\OrderProcess;
use App\Models\ProductVariants;
use App\Services\OrderService;

class OrderController extends Controller
{
    //
    protected OrderService $orderService;
    public function __construct(OrderService $orderService){
        $this->orderService = $orderService;
        // Tüm dış endpointler için "super admin", "admin" ve "order manager" yetkisi zorunlu olsun
        $this->middleware('role:super admin|admin|order manager')->only([
            'index',
            'OrderFilter',
            'store',
            'updateOrderProcess',
            'paymentOrder',
            'update',
            'destroy'
        ]);
    }

    public function index(){
        return $this->orderService->getAll();
    }
    public function filterOrders(Request $request)
    {
        return $this->orderService->filterOrders();
    }
    public function create(Request $request){
        return $this->orderService->create($request);
    }
    public function update(Request $request,$id){
        return $this->orderService->update($request,$id);
    }
    public function destroy(Request $request,$id){
        return $this->orderService->destroy($request,$id);
    }

    public function updateOrderProcess(Request $request, $id)
    {
        $user = request()->user();
        // Request'ten gelen verileri doğrula
        $validator = Validator::make($request->all(), [
            'process_id' => 'required|exists:order_process,id' // process_id doğrulaması burada yapılıyor
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_data'),
                'errors' => $validator->errors()
            ], 422);
        }

        // Doğrulanan veriyi al
        $data = $validator->validated();
        $processId = $data['process_id']; // Validated veriden process_id alınıyor

        // Siparişin var olup olmadığını kontrol et
        $order = Order::find($id);
        if(!$order){
            return response()->json([
                'message'=>'Order_not_found'
            ],404);
        }

        // Geçerli süreç kontrolü
        $currentStatus = OrderProcess::findOrFail($processId);

        // Durum geçişini kontrol et
        if (!in_array($currentStatus->name, Order::$statusTransitions[$order->current_status])) {
            return response()->json([
                'message' => 'Geçersiz durum geçişi: ' . $order->current_status . ' -> ' . $currentStatus->name
            ], 400);  // 400 Bad Request
        }

        // Order tablosundaki current_status güncelleniyor
        $order->update(['current_status' => $currentStatus->name]);

        // Duruma özel işlemleri gerçekleştir
        $this->handleStatusActions($order, $currentStatus->name);

        // Başarılı işlem mesajı
        return response()->json(['message' => 'Süreç başarıyla güncellendi'], 200);
    }



    private function handleStatusActions($order, string $status)
    {
        switch ($status) {
            case 'Order Confirm':
                $this->handleOrderConfirmation($order);
                break;
            case 'Order Preparing':
                $this->handleStockReduction($order);
                $this->handleInvoices($order);
                break;
            case 'Cargo':
                $this->handleShipping($order);
                break;
            case 'Delivery':
                $this->handleDelivery($order);
                break;
        }
    }

    private function handleOrderConfirmation(Order $order)
    {
        $order->update(['order_date' => now()]);
        $orderProduct = $order->orderProducts->first();
        $regionId = $orderProduct->region_id;
        $region = Regions::findOrFail($regionId);
        $order->update(['currency_code' => $region->currency]);
        Mail::to($order->email)->send(new OrderConfirmationMail($order));
    }

    private function handleStockReduction(Order $order)
    {
        /*foreach ($order->orderProducts as $item) {
            $variant = ProductVariants::find($item->variant_id);
            try{
                $variant->deducStock($item->quantity,$variant);
            }catch(\Exception $e){
                Log::error("Stok azaltma hatası: " . $e->getMessage());

            }
            //$variant->decrement('stock', $item->quantity);
        }*/

        DB::beginTransaction();
        try{
            foreach ($order->orderProducts as $orderProduct) {
                $variant = ProductVariants::findOrFail($orderProduct->variant_id);
                $regionId = $orderProduct->region_id;

                if ($regionId) {
                    $regionalStock = ProductVariantRegion::where([
                        ['product_variant_id', $variant->id],
                        ['region_id', $regionId]
                    ])->first();
                    $region=Regions::where('id',$regionalStock->region_id)->first();

                    if (!$regionalStock) {
                        throw new \Exception(__('messages.region_stock_not_found'));
                    }
                    $order->update(['currency_code'=>$region->currency]);
                    if ($regionalStock->stock < $orderProduct->quantity) {
                        throw new \Exception(__('messages.insufficient_stock'));
                    }

                    $regionalStock->decrement('stock', $orderProduct->quantity);
                } else {
                    if ($variant->stock < $orderProduct->quantity) {
                        throw new \Exception(__('messages.insufficient_stock'));
                    }

                    $variant->decrement('stock', $orderProduct->quantity);
                }
            }
            DB::commit();

        }
        catch(\Exception $e){
            DB::rollBack();
            Log::error("Stock reduction error: " . $e->getMessage());
            throw $e;
        }
    }
    private function handleInvoices(Order $order){
        $orderAddresses=OrderAddress::where('order_id',$order->id)->firstOrFail();
        $invoiceNumber=mt_rand(10000000000,99999999999);
        $invoiceData=[
            "order_id"=>$order->id,
            "customer_id"=>$order->customer_id,
            "invoice_number"=>$invoiceNumber,
            "invoice_date"=>Carbon::now(),
            "billing_address"=>$orderAddresses->address_type,
            "tax_amount"=>$order->tax_amount,
            "total_amount"=>$order->total_amount,
            'currency'       => $order->currency_code,
        ];
        //fatura kaydını oluşturuyoruz
        $invoice=Invoices::create($invoiceData);
        //ilişkileri yüklüyoruz
        $invoice=Invoices::with([
            'customer',
            'order.orderProducts.variant.product'
        ])->findOrFail($invoice->id);

        $pdf=PDF::loadView('invoice',compact('invoice'));
        //resources/views/invoice.blade.php
        $pdfPath = storage_path("app/public/invoices/{$invoice->invoice_number}.pdf");
        $pdf->save($pdfPath);
        // E-posta gönderimi
        Mail::to($invoice->customer->email)->send(new InvoiceMail($invoice, $pdfPath));
        return response()->json([
            'success' => true,
            'message' => 'Fatura başarıyla gönderildi'
        ],200);

    }

    private function handleShipping(Order $order)
    {
        $order->update([
            'shipping_tracking_number' => Str::upper(Str::random(10)),
            'shipment_date' => now(),
            'delivery_date' => now()->addDays(3)
        ]);
        Mail::to($order->email)->send(new OrderPreparedMail($order));
    }

    private function handleDelivery(Order $order)
    {
        $order->update(['delivery_date' => now()]);
        Mail::to($order->email)->send(new OrderDeliveredMail($order));
    }








    //Ödemi İslemine geçiş ->payment_controller da ödemeye devam ediliyor
    public function paymentOrder(Request $request,$id){
        $user=$request->user();

        $order=Order::find($id);
        if(!$order){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ],404);
        }
        $orderProduct=$order->orderProducts()->exists();
        $orderAddresses=$order->addresses()->exists();
        if(!$orderProduct || !$orderAddresses){
            return response()->json([
                'success' => false,
                'message' => __('messages.no_order_products_found') // "Eklenmiş ürün bulunmamaktadır" mesajını döndüren çeviri anahtarı
            ], 404);
        }
        $currentProcess=$order->orderProcesses()->latest()->first();
        if(!$currentProcess || $currentProcess->status!='Sipariş Oluşturma'){
            return response()->json([
                'success'=>false,
                'message' => __('messages.only_allowed_in_creation_phase')
            ],403);
        }

        $order->orderProcesses()->update([
            'status'=>'Ödeme İşlemi',
            'description'=>'Ödeme işlemi gerçekleştirilecek'
        ]);
        return response()->json([
            'success' => true,
            'message' => __('messages.order_paymented')
        ],200);
    }

}