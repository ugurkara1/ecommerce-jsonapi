<?php

namespace App\Repositories;

use App\Contracts\OrderContract;
use App\Models\Order;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Validator;

class OrderRepository implements OrderContract
{
    /**
     * Tüm siparişleri ilişkileriyle birlikte getir.
     */
    public function getAll()
    {
        return Order::with(['customer', 'payment', 'orderProducts.variant'])->get();
    }

    /**
     * Siparişleri filtreleyerek getir.
     */
    public function filterOrders()
    {
        $query = Order::with(['customer', 'payment', 'orderProducts.variant']);

        return QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('order_number'),
                AllowedFilter::partial('slug'),
            ])
            ->get();
    }



    public function create(array $data, $user){
        $order=Order::create($data);
        activity()
            ->causedBy($user)
            ->performedOn($order)
            ->withProperties(['attributes'=>$data])
            ->log('Order created successfully');
        return $order;
    }
    public function update(array $data,$id,$user){
        $order=Order::where('id',$id)->first();
        if(!$order){
            return null;
        }
        $order->update($data);
        activity()
            ->causedBy($user)
            ->performedOn($order)
            ->withProperties(['attributes'=>$data])
            ->log('Order updated successfully');

        return $order;
    }
    public function delete($id,$user){
        $order=Order::where('id',$id)->first();
        if(!$order){
            return null;
        }
        $order->delete();
        activity()
            ->causedBy($user)
            ->performedOn($order)
            ->log('Order deleted successfully');
        return $order;
    }
}
