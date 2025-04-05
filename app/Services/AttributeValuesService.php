<?php

namespace App\Services;

use App\Contracts\AttributesContract;
use App\Contracts\AttributeValuesContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class AttributeValuesService
{
    //
    protected $attributeValuesRepository;
    public function __construct(AttributeValuesContract $attributeValuesRepository) {
        $this->attributeValuesRepository = $attributeValuesRepository;
    }

    public function getAll() {
        return response()->json([
            'success' => true,
            'message' => __('messages.attribute_values_listed'),
            'data' => $this->attributeValuesRepository->getAll()
        ],200);
    }
    public function show($id) {
        $attributesValues=$this->attributeValuesRepository->show($id);
        if (!$attributesValues) {
            return response()->json([
                'success' => false,
                'message' => __('messages.attribute_values_not_found'),
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.attribute_values_show'),
            'data' => $this->attributeValuesRepository->show($id)
        ],200);
    }

    public function create(Request $request, $attrId)
    {
        $validated = $this->validateRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }
        $attributeValues = $this->attributeValuesRepository->create($validated,  $request->user(),$attrId);
        return response()->json([
            'success' => true,
            'message' => __('messages.attribute_values_created'),
            'data' => $attributeValues,
        ], 201);
    }
    public function update(Request $request, $id) {
        $attributeValues=$this->attributeValuesRepository->show($id);
        if (!$attributeValues) {
            return response()->json([
                'success' => false,
                'message' => __('messages.attribute_values_not_found'),
            ], 404);
        }
        $validated = $this->validateRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }
        $attributeValues = $this->attributeValuesRepository->update($validated, $id, $request->user());
        return response()->json([
            'success' => true,
            'message' => __('messages.attribute_values_updated'),
            'data' => $attributeValues,
        ], 200);
    }

    public function delete(Request $request, $id) {
        $attributeValues=$this->attributeValuesRepository->show($id);
        if (!$attributeValues) {
            return response()->json([
                'success' => false,
                'message' => __('messages.attribute_values_not_found'),
            ], 404);
        }

        $attributeValues = $this->attributeValuesRepository->delete($id, $request->user());
        return response()->json([
            'success' => true,
            'message' => __('messages.attribute_values_deleted'),
            'data' => $attributeValues,
        ], 200);
    }





    public function validateRequest(Request $request){
        $rules = [
            'value' => 'required|string',
        ];
        $validator=Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }
        return $validator->validate();
    }
}
