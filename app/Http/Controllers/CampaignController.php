<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaigns;
use App\Services\CampaignService;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    protected CampaignService $campaignService;
    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
        // Only users with these roles can create or update campaigns
        $this->middleware('role:super admin|admin|discount manager')->only(['store', 'update']);
        // Only users with these roles can delete campaigns
        $this->middleware('role:super admin|admin|discount manager')->only('destroy');
    }
    public function index(){
        return $this->campaignService->getAll();
    }
    public function show($id){
        return $this->campaignService->show($id);
    }
    public function store(Request $request){
        return $this->campaignService->create($request);
    }
    public function update(Request $request, $id){
        return $this->campaignService->update($request, $id);
    }
    public function delete(Request $request, $id){
        return $this->campaignService->delete($request, $id);
    }
}
