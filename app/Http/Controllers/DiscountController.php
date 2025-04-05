<?php

namespace App\Http\Controllers;

use App\Models\Discounts;
use App\Models\Products;
use App\Models\ProductVariants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\DiscountService;

class DiscountController extends Controller
{
    protected DiscountService $discountService;
    public function __construct(DiscountService $discountService){
        // Sadece bu rollerdeki kullanıcılar indirim oluşturabilir veya güncelleyebilir
        $this->discountService=$discountService;
        $this->middleware('role:super admin|admin|discount manager')->only(['store', 'update']);
        // Sadece bu rollerdeki kullanıcılar indirim silebilir
        $this->middleware('role:super admin|admin|discount manager')->only('destroy');
    }
    public function index(){
        return $this->discountService->getAll();
    }
    public function show($id){
        return $this->discountService->show($id);
    }
    public function create(Request $request){
        return $this->discountService->create($request);
    }
    public function update(Request $request, $id){
        return $this->discountService->update($request, $id);
    }
    public function delete(Request $request, $id){
        return $this->discountService->delete($request, $id);
    }
    public function endDiscount(Request $request, $id){
        return $this->discountService->endDiscount($request, $id);
    }
}