<?php

namespace App\Repositories;
use App\Models\AttributeValues;
use App\Contracts\AttributeValuesContract;

class AttributeValuesRepository implements AttributeValuesContract
{
    public function getAll()
    {
        return AttributeValues::all();
    }
    public function show($id)
    {
        return AttributeValues::find($id);
    }
    public function create(array $data, $user,$attrId)
    {
        if (!empty($attrId)) {
            $data['attribute_id'] = $attrId;
        }
        $attributeValue = AttributeValues::create($data);
        activity()
            ->performedOn($attributeValue)
            ->causedBy($user)
            ->withProperties($data)
            ->log('Attribute Value created');
        return $attributeValue;
    }
    public function update(array $data, $id, $user)
    {
        $attributeValue = AttributeValues::where('id',$id)->first();
        $attributeValue->update($data);
        activity()
            ->performedOn($attributeValue)
            ->causedBy($user)
            ->withProperties($data)
            ->log('Attribute Value updated');
        return $attributeValue;
    }
    public function delete($id, $user)
    {
        $attributeValue = AttributeValues::where('id',$id)->first();
        $attributeValue->delete();
        activity()
            ->performedOn($attributeValue)
            ->causedBy($user)
            ->log('Attribute Value deleted');
        return $attributeValue;
    }
}
