<?php

namespace App\Services;
use App\Repositories\DiscountRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DiscountService{
    protected $discountRepository;
    public function __construct(DiscountRepository $discountRepository){
        $this->discountRepository = $discountRepository;
    }
    public function getAll(){
        return response()->json([
            'success' => true,
            'message' => __('messages.discount_listed'),
            'data' => $this->discountRepository->getAll()
        ],200);

    }
    public function show($id){
        $discount=$this->discountRepository->show($id);
        if (!$discount) {
            return response()->json([
                'success' => false,
                'message' => __('messages.discount_not_found'),
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.discount_show'),
            'data' => $this->discountRepository->show($id)
        ],200);
    }
    public function create(Request $request){
        $validated = $this->validateRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }
        $discount = $this->discountRepository->create($validated,  $request->user());
        return response()->json([
            'success' => true,
            'message' => __('messages.discount_created'),
            'data' => $discount,
        ], 201);

    }
    private function validateRequest(Request $request)
    {
        $rules = [
            'name'           => 'required|string|max:255',
            'discount_type'  => 'sometimes|required|in:percentage,fixed,buy_x_get_y',
            'value'          => 'sometimes|required|numeric|min:0',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'applies_to'     => 'sometimes|required|in:all,categories,products,variants,brands,segments',
            'is_active'      => 'sometimes|boolean',
            'category_ids'   => 'required_if:applies_to,categories|array',
            'category_ids.*' => 'exists:categories,id',
            'product_ids'    => 'required_if:applies_to,products|array',
            'product_ids.*'  => 'exists:products,id',
            'variant_ids'    => 'required_if:applies_to,variants|array',
            'variant_ids.*'  => 'exists:product_variants,id',
            'brand_ids'      => 'required_if:applies_to,brands|array',
            'brand_ids.*'    => 'exists:brands,id',
            'segment_ids'    => 'required_if:applies_to,segments|array',
            'segment_ids.*'  => 'exists:segments,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_data'),
                'errors'  => $validator->errors(),
            ], 422);
        }

        return $validator->validated();
    }
    public function update(Request $request, $id){
        $validated = $this->validateRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }
        $discount = $this->discountRepository->update($validated, $id, $request->user());
        return response()->json([
            'success' => true,
            'message' => __('messages.discount_updated'),
            'data' => $discount,
        ], 200);
    }
    public function delete(Request $request, $id){
        $discount = $this->discountRepository->delete($id, $request->user());
        if(!$discount){
            return response()->json([
                'success' => false,
                'message' => __('messages.discount_not_found'),
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.discount_deleted'),
            'data' => $discount,
        ], 200);
    }
    public function endDiscount(Request $request, $discountId)
    {
        $discount = $this->discountRepository->endDiscount($discountId, $request->user());
        if (!$discount) {
            return response()->json([
                'success' => false,
                'message' => __('messages.discount_not_found'),
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.discount_ended'),
            'data'    => $discount,
        ], 200);
    }
}