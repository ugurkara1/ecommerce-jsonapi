<?php

namespace App\Repositories;

use App\Contracts\InvoicesContract;
use App\Models\Invoices;

class InvoicesRepository implements InvoicesContract{
    public function getAll(){
        $invoices = Invoices::with('order', 'customer')->get();
        return $invoices;
    }
    public function show($id){
        $invoice = Invoices::with('order', 'customer')->find($id);
        return $invoice;
    }
    public function create(array $data, $user){
        $invoices=Invoices::create($data);
        activity()
            ->causedBy($user)
            ->performedOn($invoices)
            ->withProperties(['invoice' => $data])
            ->log('Invoice created successfully');
        return $invoices;
    }
    public function update(array $data, $id,$user){
        $invoices=Invoices::where('id',$id)->first();
        if(!$invoices){
            return null;
        }
        $invoices->update($data);
        activity()
            ->causedBy($user)
            ->performedOn($invoices)
            ->withProperties(['invoice' => $data])
            ->log('Invoice updated successfully');
        return $invoices;
    }
    public function delete($id,$user){
        $invoices=Invoices::where('id',$id)->first();
        if(!$invoices){
            return null;
        }
        $invoices->delete();
        activity()
            ->causedBy($user)
            ->performedOn($invoices)
            ->log('Invoice deleted successfully');
        return $invoices;
    }
}
