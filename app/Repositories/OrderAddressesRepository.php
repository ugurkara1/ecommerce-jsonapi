<?php

namespace App\Repositories;

use App\Contracts\OrderAddressesContract;
use App\Models\OrderAddress;

class OrderAddressesRepository implements OrderAddressesContract{
    public function getAll(){
        return OrderAddress::with('orders')->get();
    }
    public function show($id){
        return OrderAddress::with('orders')->findOrFail($id);
    }
    public function create(array $data,$user){
        $address=OrderAddress::create($data);
        activity()
            ->causedBy($user)
            ->performedOn($address)
            ->withProperties(['attributes'=>$data])
            ->log('OrderAddress created successfully');
        return $address;
    }

    public function update(array $data,$id,$user){
        $address=OrderAddress::where('id',$id)->first();
        if(!$address){
            return null;
        }
        $address->update($data);
        activity()
            ->causedBy($user)
            ->performedOn($address)
            ->withProperties(['attributes'=>$address])
            ->log('Order Address updated successfully');
        return $address;
    }
    public function destroy($id,$user){
        $address=OrderAddress::where('id',$id)->first();
        if(!$address){
            return null;
        }
        $address->delete();
        activity()
            ->causedBy($user)
            ->performedOn($address)
            ->log('Order address deleted successfully');
        return $address;
    }
}
