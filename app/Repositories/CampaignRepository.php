<?php

namespace App\Repositories;

use App\Contracts\CampaignContract;
use App\Models\Campaigns;

class CampaignRepository implements CampaignContract
{
    //
    public function getAll()
    {
        return Campaigns::all();
    }
    public function show($id)
    {
        return Campaigns::find($id);
    }
    public function create(array $data, $user)
    {
        $campaign = Campaigns::create($data);
        activity()
            ->performedOn($campaign)
            ->causedBy($user)
            ->withProperties($data)
            ->log('Campaign created');
        return $campaign;
    }
    public function update(array $data, $id, $user){
        $campaign = Campaigns::where('id',$id)->first();
        $campaign->update($data);
        activity()
            ->performedOn($campaign)
            ->causedBy($user)
            ->withProperties($data)
            ->log('Campaign updated');
        return $campaign;
    }
    public function delete($id, $user){
        $campaign = Campaigns::where('id',$id)->first();
        $campaign->delete();
        activity()
            ->performedOn($campaign)
            ->causedBy($user)
            ->log('Campaign deleted');
        return $campaign;
    }
}