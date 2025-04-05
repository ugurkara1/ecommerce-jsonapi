<?php

namespace App\Contracts;

interface CategoryContract
{
    //
    public function getAllCategories();
    public function show($id);
    public function createCategory(array $data, $user);
    public function updateCategory($id, array $data, $user);
    public function deleteCategory($id, $user);

}
