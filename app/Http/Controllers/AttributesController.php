<?php

namespace App\Http\Controllers;

use App\Models\Attributes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\AttributesService;

class AttributesController extends Controller
{
    //
    protected AttributesService $attributesService;
    public function __construct(AttributesService $attributesService){
        $this->attributesService = $attributesService;
        $this->middleware('role:super admin|admin|product manager')->only('store','update');
        $this->middleware('role:super admin|admin')->only('destroy');
    }

    public function index(){
        return $this->attributesService->getAll();
    }
    public function show($id){
        return $this->attributesService->show($id);
    }
    public function store(Request $request){
        return $this->attributesService->create($request);
    }
    public function update(Request $request,$id){
        return $this->attributesService->update($request,$id);
    }
    public function destroy(Request $request,$id){
        return $this->attributesService->delete($request,$id);
    }


}