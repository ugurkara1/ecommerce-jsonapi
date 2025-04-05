<?php

namespace App\Contracts;

interface AttributesContract
{
    //
    public function getAll();
    public function show($id);
    public function create(array $data,$user);
    public function update($id, array $data,$user);
    public function delete($id,$user);
}