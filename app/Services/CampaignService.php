<?php

namespace App\Services;

use App\Contracts\CampaignContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class CampaignService
{
    //
    protected $campaignRepository;

    public function __construct(CampaignContract $campaignRepository)
    {
        $this->campaignRepository = $campaignRepository;
    }
    public function getAll()
    {
        return response()->json([
            'success' => true,
            'message' => __('messages.campaigns_listed'),
            'data' => $this->campaignRepository->getAll()
        ], 200);
    }
    public function show($id){
        $campaigns=$this->campaignRepository->show($id);
        if (!$campaigns) {
            return response()->json([
                'success' => false,
                'message' => __('messages.campaigns_not_found'),
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => __('messages.campaigns_show'),
            'data' => $this->campaignRepository->show($id)
        ], 200);

    }
    public function create(Request $request)
    {
        $validated = $this->validateRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }
        $campaign = $this->campaignRepository->create($validated, $request->user());
        return response()->json([
            'success' => true,
            'message' => __('messages.campaigns_created'),
            'data' => $campaign,
        ], 201);
    }
    public function update(Request $request, $id)
    {
        $validated = $this->validateRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }
        $campaign = $this->campaignRepository->update($validated, $id, $request->user());
        return response()->json([
            'success' => true,
            'message' => __('messages.campaigns_updated'),
            'data' => $campaign,
        ], 200);
    }
    public function delete(Request $request, $id)
    {
        $campaign = $this->campaignRepository->delete($id, $request->user());
        return response()->json([
            'success' => true,
            'message' => __('messages.campaigns_deleted'),
            'data' => $campaign,
        ], 200);
    }
    public function validateRequest(Request $request)
    {
        $rules=[
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'target_audience' => 'nullable|string',
            'is_active' => 'required|boolean',
            'campaign_type' => 'nullable|string'
        ];
        $validator=Validator::make($request->all(),$rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        return $validator->validate();
    }
}