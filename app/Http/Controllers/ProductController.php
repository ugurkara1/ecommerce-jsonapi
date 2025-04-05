<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService){
        $this->productService = $productService;

        $this->middleware('role:super admin|admin|product manager')->only(['store', 'update','show','index']);
        $this->middleware('role:super admin|admin')->only('destroy');
    }

    public function index() {
        return $this->productService->getAll();

    }
    public function show($id) {
        return $this->productService->show($id);
    }
    public function create(Request $request) {
        return $this->productService->create($request);
    }
    public function update(Request $request, $id) {
        return $this->productService->update($request, $id);
    }
    public function destroy(Request $request,$id) {
        return $this->productService->delete($request,$id);
    }
}