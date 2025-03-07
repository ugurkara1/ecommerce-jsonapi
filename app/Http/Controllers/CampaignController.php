<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaigns;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    //
    public function index(){
        $campaigns=Campaigns::all();
        return response()->json([
            'success'=>true,
            'message'=>__('messages.campaign_listed'),
            'data'=>$campaigns
        ]);
    }
    public function show($id){
        $campaigns=Campaigns::findOrFail($id);
        return response()->json([
            'success'=>true,
            'message'=>__('messages.campaigns_showed'),
            'data'=>$campaigns
        ]);
    }
    public function store(Request $request){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>true,
                'message'=>__('messages.unauthorized')
            ]);
        }

        $validator=Validator::make($request->all(),[
            'name'=>'required|string|max:255',
            'description'=>'required|string',
            'start_date'=>'required|date',
            'end_date'=>'required|date|after_or_equal:start_date',
            'target_audience'=>'nullable|string',
            'is_active'=>'required|boolean',
            'campaign_type'=>'nullable|string'
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data'),
                'errors'=>$validator->errors()
            ]);
        }

        $data=$validator->validated();
        $campaigns=Campaigns::create($data);

        return response()->json([
            'success'=>true,
            'message'=>__('messages.campaign_successfully'),
            'data'=>$campaigns
        ],200);
    }
    public function update(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
                'errors'=>$user->errors()
            ],403);
        }

        $validator=Validator::make($request->all(),[
            'name'=>'required|string|max:255',
            'description'=>'required|string',
            'start_date'=>'required|date',
            'end_date'=>'required|date|after_or_equal:start_date',
            'target_audience'=>'nullable|string',
            'is_active'=>'required|boolean',
            'campaign_type'=>'nullable|string'
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.invalid_data')
            ],422);
        }
        $validated=$validator->validated();
        try{
            $campaigns=Campaigns::where('id',$id)->first();
            if(!$campaigns){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.campaigns_not_found')
                ],404);
            }
            $campaigns->update($validated);
            return response()->json([
                'success'=>true,
                'message'=>__('messages.campaigns_updated'),
                'data'=>$campaigns
            ],200);

        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error'),
                'errors'=>$e->getMessage()
            ],500);
        }
    }

    public function destroy(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage orders')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
                'errors'=>$user->errors()
            ],403);
        }
        try{

            $campaign=Campaigns::where('id',$id)->first();
            if(!$campaign){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.campaign_not_found')
                ],404);
            }
            $campaignData=[
                'id'=>$campaign->id,
                'name'=>$campaign->name,
                'description'=>$campaign->description,
                'start_date'=>$campaign->start_date,
                'end_date'=>$campaign->end_date,
                'target_audience'=>$campaign->target_audience,
                'is_active'=>$campaign->is_active,
                'campaign_type'=>$campaign->campaign_type
            ];

            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($campaign)
                ->withProperties(['attributes'=>$campaignData])
                ->log("Function Name: $functionName.Brands deleted successfully.");
            $campaign->delete();
            return response()->json([
                'success'=>true,
                'data'=>$campaign,
                'messages'=>__('messages.payment_deleted'),
            ]);


        }catch(\Exception $e){

            return response()->json([
                'success'=>false,
                'data'=>$campaign,
                'message'=>__('messages.service_error')
            ]);


        }


    }
}
