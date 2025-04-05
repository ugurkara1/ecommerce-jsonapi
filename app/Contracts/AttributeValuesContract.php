<?php

namespace App\Contracts;

interface AttributeValuesContract
{
    //
    public function getAll();
    public function show($id);
    public function create(array $data, $user, $attrId);
    public function update(array $data, $id, $user);
    public function delete($id, $user);
}