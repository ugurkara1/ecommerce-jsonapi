<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
use Illuminate\Support\Str;
use App\Mail\InvoiceMail;
use App\Mail\OrderPreparedMail;
use App\Models\Invoices;
use App\Models\OrderAddress;
use App\Models\ProductVariants;

class OrderController extends Controller
{
    //
    public function index(Request $request){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return  response()->json([
                'succes'=>false,
                'message'=>__('messages.unauthorized')
            ],403);
        }
        $order=Order::with(['customer','payment','orderProducts.variant'])->get();
        return response()->json([
            'success'=>true,
            'message'=>__('messages.order_listed'),
            'data'=>$order
        ],200);
    }


    public function OrderFilter(Request $request){
        // Tüm verileri alıyoruz
        $orders = Order::with(['customer', 'payment', 'orderProducts.variant']);

        // Filtre uygulama
        $filteredOrders = QueryBuilder::for($orders)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('order_number'),
                AllowedFilter::partial('slug'),
            ])
            ->get();

        // Eğer filtre uygulandıktan sonra sonuç boşsa, 404 döndür
        if ($filteredOrders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.order_not_found')
            ], 404);
        }

        // Filtrelenmiş verilerle birlikte sonuçları döndür
        return response()->json([
            'success' => true,
            'data' => $filteredOrders
        ],200);
    }

    public function store(Request $request){
        //$customer_Id=$request->user()->id;

        $user=$request->user();
        $validator=Validator::make($request->all(),[
            'customer_id'=>'required|exists:customers,id',
            'order_number' => 'required|string|unique:orders,order_number|regex:/^[0-9]{11}$/',
            "full_name"=>'required|string|max:255',
            "email"=>'required|email',
            "phone_number"=>'required|string|max:11',
            //'payment_id'=>'required|exists:payments,id',
            //'order_date'=>'required|date',
            'currency_code'=>'required|in:USD,EUR,GBP,TRY,JPY,AUD,CAD',
            'currency_rate'=>'required|numeric',
            //'subtotal'=>'required|numeric',
            //'tax_amount'=>'required|numeric', //vergi tutarı
            //'shipping_cost'=>'required|numeric', //kargo tutarı
            //'total_amount'=>'required|numeric', //toplam tutar
            //'shipping_tracking_number'=>'nullable|string', //kargo takip numarası
            //'shipment_date'=>'nullable|date', //sevkiyat tarihi
            //'delivery_date'=>'nullable|date' //teslimat tarihi
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data'),
                'errors'=>$validator->errors()
            ],422);
        }
        $data=$validator->validated();
        //$data['customer_id']=$customer_Id;
        $data['subtotal']=0;
        $data['tax_amount']=0;
        $data['shipping_cost']=0;
        $data['total_amount']=0;
        //500TL ve üzeri alışverişlerde kargo bedava
        /*if($data['total_amount']>=500){
            $campaign=Campaigns::where('campaign_type','free_shipping')
                ->where('is_active',true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->first();

            if($campaign){
                $data['shipping_cost']=0;
                $data['campaign_id']=$campaign->id;
            }

        }*/

        $order=Order::create($data);
        //ilk sipariş aşamasını oluştur
        $order->orderProcesses()->create([
            'status'=>'Sipariş oluşturma',
            'description'=>'Sipariş başlatıldı'
        ]);
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

    //Ödemi İslemine geçiş ->payment_controller da ödemeye devam ediliyor
    public function paymentOrder(Request $request,$id){
        $user=$request->user();
        if (!$user->hasPermissionTo('manage orders')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }
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

    //Sipariş Onayı aşaması:Sipariş hazırrlanma aşamasına geçiş sağlıyor. //Siparis Onay maili ve Sipariş faturası gidiyor.
    public function confirmOrder(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermissionTo('manage orders')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $order = Order::findOrFail($id);
        $currentProcess = $order->orderProcesses()->latest()->first();
        if (!$currentProcess || $currentProcess->status != "Sipariş Onayı") {
            return response()->json([
                'success' => false,
                'message' => __('messages.allowed_only_at_order_confirmation_phase')
            ], 403);
        }

        //Siparişe ait tüm ürünler üzerinden stok düşmesi yapılır
        foreach($order->orderProducts as $orderProduct){
            $variant=ProductVariants::findOrFail($orderProduct->variant_id);
            $variant->decrement('stock', $orderProduct->quantity);
        }

        // Sipariş sürecini güncelle
        $order->orderProcesses()->update([
            'status' => 'Hazırlık',
            'description' => 'Ürün hazırlanıyor'
        ]);
        $order->update([
            'order_date' => Carbon::now(),
        ]);

        // Sipariş onay e-postasını gönder
        try {
            Mail::to($order->email)->send(new OrderConfirmationMail($order));
        } catch (\Exception $e) {
            Log::error('E-posta gönderilemedi: ' . $e->getMessage());
        }

        // Fatura oluşturma işlemi
        try {
            // Siparişin fatura adresi bilgilerini al (OrderAddress tablosundan)
            $orderAddress = OrderAddress::where('order_id', $order->id)->firstOrFail();

            // 11 haneli benzersiz fatura numarası oluştur (örneğin, rastgele sayı ile)
            $invoiceNumber = mt_rand(10000000000, 99999999999);

            $invoiceData = [
                'order_id'       => $order->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date'   => Carbon::now(), // İşlemin gerçekleştiği zaman
                'customer_id'    => $order->customer_id,
                'billing_address'=> $orderAddress->address_type,
                'tax_amount'     => $order->tax_amount,
                'total_amount'   => $order->total_amount,
                'currency'       => $order->currency_code,
            ];

            // Fatura kaydını oluştur
            $invoice = Invoices::create($invoiceData);

            // Aktivite kaydı oluştur (opsiyonel)
            activity()
                ->causedBy($user)
                ->performedOn($invoice)
                ->withProperties($invoiceData)
                ->log("Fatura oluşturuldu.");
        } catch (\Exception $e) {
            Log::error('Fatura oluşturulurken hata: ' . $e->getMessage());
            // Fatura oluşturma hatası durumunda sipariş onayı iptal edilmiyor.
            // İsteğe bağlı olarak burada hata mesajı döndürebilir veya farklı bir işlem yapabilirsiniz.
        }
        try{
            // İlişkileri yüklüyoruz
            $invoice = Invoices::with([
                'customer',
                'order.orderProducts.variant.product' // Ürün bilgilerini getir
            ])->findOrFail($invoice->id);
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
            ],200);
        }catch(\Exception $e){
            Log::error('Fatura gönderim hatası: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.order_confirmed_and_invoice_created')
        ],200);
    }

    //Sipariş Hazırlanıyor vr kargoya veriliyor. Bu aşamada mail olarak kargo takip numarası ve detay bilgileri müşteriye mail olarak aktarılıyor
    public function orderPreparing(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ],403);
        }
        $order=Order::findOrFail($id);
        if(!$order){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.order_not_found')
            ],404);
        }
        $currentstatus=$order->orderProcesses()->latest()->first();
        if(!$currentstatus || $currentstatus->status!="Hazırlık"){
            return response()->json([
                'success'=>false,
                'message' => __('messages.allowed_only_at_order_prepare_phase')
            ],403);
        }
        // Sipariş sürecini güncelle
        $order->orderProcesses()->update([
            'status' => 'Kargo ve Teslimat',
            'description' => 'Ürününüz sizlere ulaşmak üzere yola çıkmıştır'
        ]);
        $order->update([
            'shipping_tracking_number'=>strtoupper(Str::random(10)),
            'shipment_date'=>Carbon::now(),
            'delivery_date'=>Carbon::now()->addDays(3),
        ]);
        try {
            Mail::to($order->email)->send(new OrderPreparedMail($order));
        } catch (\Exception $e) {
            Log::error('E-posta gönderilemedi: ' . $e->getMessage());
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.order_prepared')
        ],200);
    }

    //Sipariş Teslim edildi:Bu aşamada sipariş teslim ediliyor ve müşteriye teslima aldığını içeren bilgi aktarılıyor.
    public function orderDelivered(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ],403);
        }
        $order=Order::findOrFail($id);
        if(!$order){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.order_not_found')
            ],404);
        }
        $currentstatus=$order->orderProcesses()->latest()->first();
        if(!$currentstatus || $currentstatus->status!="Kargo ve Teslimat"){
            return response()->json([
                'success'=>false,
                'message' => __('messages.allowed_only_at_order_delivery_stage')
            ],403);
        }
        // Sipariş sürecini güncelle
        $order->orderProcesses()->update([
            'status' => 'Teslimat ve Onay',
            'description' => 'Ürününüz sizlere ulaşmak üzere yola çıkmıştır'
        ]);

        $order->update([
            'delivery_date'=>Carbon::now(),
        ]);
        try {
            Mail::to($order->email)->send(new OrderDeliveredMail($order));
        } catch (\Exception $e) {
            Log::error('E-posta gönderilemedi: ' . $e->getMessage());
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.order_delivered')
        ],200);
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
                //'order_status'=>$order->order_status,
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
            ],500);
        }
    }

/*
    //Sipariş Durumu Güncelleme
    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();

        // Kullanıcının siparişleri yönetme izni olup olmadığını kontrol et
        if (!$user->hasPermissionTo('manage orders')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ], 403);
        }

        // Gelen verinin doğrulamasını yap
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:pending,processing,shipped,delivered,canceled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_data'),
                'errors' => $validator->errors()
            ], 422);
        }

        // Siparişi bul
        $order = Order::where('id',$id)->first();

        // Sipariş bulunamadıysa hata döndür
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => __('messages.order_not_found')
            ], 404);
        }

        // Orijinal durumu kaydet
        $originalStatus = $order->order_status;

        // Yeni durumu güncelle
        $order->update(['order_status' => $request->order_status]);

        // Durum güncelleme işlemini logla
        $functionName = __FUNCTION__;
        activity()
            ->causedBy($user)
            ->performedOn($order)
            ->withProperties([
                'original_status' => $originalStatus,
                'new_status' => $request->order_status
            ])
            ->log("Function Name: $functionName. Order status updated from $originalStatus to " . $request->order_status);

        // Yanıt döndür
        return response()->json([
            'success' => true,
            'message' => __('messages.order_status_updated'),
            'new_status' => $order->order_status
        ], 200);
    }
*/

}
