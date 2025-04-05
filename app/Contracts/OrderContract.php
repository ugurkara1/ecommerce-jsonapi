<?php

namespace App\Contracts;

use Illuminate\Http\Request;



interface OrderContract
{
    //
    public function getAll();
    public function filterOrders();
    public function create(array $data,$user);
    public function update(array $data,$id,$user);
    public function delete($id,$user);
}
