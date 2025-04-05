<?php

namespace App\Http\Controllers;

use App\Services\AttributeValuesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Attributes;
use App\Models\AttributeValues;

class AttributeValuesController extends Controller
{
    protected AttributeValuesService $attributeValuesService;
    public function __construct(AttributeValuesService $attributeValuesService)
    {
        $this->attributeValuesService = $attributeValuesService;
        $this->middleware('role:super admin|admin|product manager')->only(['store', 'update']);
        $this->middleware('role:super admin|admin')->only('destroy');
    }
    public function index(){
        return $this->attributeValuesService->getAll();
    }
    public function show($id){
        return $this->attributeValuesService->show($id);
    }
    public function store(Request $request,$attrId){
        return $this->attributeValuesService->create($request, $attrId);
    }
    public function update(Request $request, $id){
        return $this->attributeValuesService->update($request, $id);
    }
    public function delete(Request $request, $id){
        return $this->attributeValuesService->delete($request, $id);
    }

}