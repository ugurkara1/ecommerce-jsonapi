<?php

// Contracts Katmanı İçinde BrandContract.php Dosyası

namespace App\Contracts;

interface BrandContract
{
    public function getAllBrands();
    public function show($id);
    public function createBrand(array $data, $user);
    public function updateBrand($id, array $data, $user);
    public function deleteBrand($id, $user);
}