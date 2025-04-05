<?php

namespace App\Contracts;

interface DiscountContract
{
    //
    public function getAll();
    public function show($id);
    public function create(array $data, $user);
    public function update(array $data, $id, $user);
    public function delete($id, $user);
    public function endDiscount($discountId, $user);

}
