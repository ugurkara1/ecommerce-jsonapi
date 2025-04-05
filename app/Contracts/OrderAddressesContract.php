<?php

namespace App\Contracts;

interface OrderAddressesContract
{
    //
    public function getAll();
    public function show($id);
    public function create(array $data,$user);
    public function update(array $data,$id,$user);
    public function destroy($id,$user);
}