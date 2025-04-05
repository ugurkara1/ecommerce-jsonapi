<?php

namespace App\Http\Controllers;

use App\Models\OrderAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\OrderProcess;
use App\Services\OrderAddressesService;

class OrderAdressesController extends Controller
{
    protected OrderAddressesService $orderAddressesService;
    public function __construct(OrderAddressesService $orderAddressesService){
        $this->orderAddressesService = $orderAddressesService;
        // Adres ekleme, güncelleme, silme işlemleri sadece yöneticiler ve sipariş yöneticileri tarafından yapılabilir.
        $this->middleware('role:super admin|admin|order manager')->only(['store', 'update', 'destroy']);

        // Tüm sipariş adreslerini sadece yöneticiler ve sipariş yöneticileri listeleyebilir.
        // Ancak müşteriler sadece kendi siparişlerine ait adresleri görebilir.
        $this->middleware('role:super admin|admin|order manager|customer')->only('index', 'show');
    }
    public function index(){
        return $this->orderAddressesService->getAll();
    }
    public function show($id){
        return $this->orderAddressesService->show($id);
    }
    public function create(Request $request){
        return $this->orderAddressesService->create($request);
    }
    public function update(Request $request,$id){
        return $this->orderAddressesService->update($request,$id);
    }
    public function delete(Request $request,$id){
        return $this->orderAddressesService->destroy($request,$id);
    }



}