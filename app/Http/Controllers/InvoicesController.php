<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\Invoices;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Services\InvoicesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicesController extends Controller
{
    protected InvoicesService $invoicesService;
    public function __construct(InvoicesService $invoicesService)
    {
        $this->invoicesService = $invoicesService;
        $this->middleware('role:super admin|admin|order manager')->only(['update', 'destroy']);
        $this->middleware('role:super admin|admin|order manager')->only(['index', 'show']);
        $this->middleware('role:super admin|admin|order manager')->only('sendInvoiceEmail');
    }

    public function index(){
        return $this->invoicesService->getAll();
    }
    public function show($id){
        return $this->invoicesService->show($id);
    }
    public function store(Request $request){
        return $this->invoicesService->create($request);
    }
    public function update(Request $request, $id){
        return $this->invoicesService->update($request, $id);
    }
    public function destroy(Request $request,$id){
        return $this->invoicesService->delete($request,$id);
    }
        // Sipariş faturasını mail olarak göndermek için
    public function sendInvoiceEmail(Request $request, $id)
    {
        $user = $request->user();

        try {
            // İlişkileri yüklüyoruz
            $invoice = Invoices::with([
                'customer',
                'order.orderProducts.variant.product' // Ürün bilgilerini getir
            ])->findOrFail($id);

            // PDF oluşturma
            $pdf = Pdf::loadView('invoice', compact('invoice'));
            // resources/views/invoice.blade.php
            $pdfPath = storage_path("app/public/invoices/{$invoice->invoice_number}.pdf");
            $pdf->save($pdfPath);

            // E-posta gönderimi
            Mail::to($invoice->customer->email)->send(new InvoiceMail($invoice, $pdfPath));

            return response()->json([
                'success' => true,
                'message' => 'Fatura başarıyla gönderildi'
            ],200);

        } catch (\Exception $e) {
            Log::error('Fatura gönderim hatası: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Fatura gönderilemedi',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
