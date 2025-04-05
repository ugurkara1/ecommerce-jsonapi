<?php
namespace App\Repositories;

use App\Contracts\AttributesContract;
use App\Models\Attributes; // Import the correct namespace for Attributes

class AttributesRepository implements AttributesContract{


    public function getAll()
    {
        return Attributes::with('values')->get();
    }
    public function show($id){
        return Attributes::with('values')->find($id);
    }
    public function create(array $data, $user){
        $attribute = Attributes::create($data);
        activity()
            ->performedOn($attribute)
            ->causedBy($user)
            ->withProperties($data)
            ->log('Attribute created');
        return $attribute;
    }

    public function update($id, array $data, $user){
        $attribute = Attributes::where('id',$id)->first();
        if (!$attribute) {
            return response()->json([
                'success' => false,
                'message' => __('messages.attribute_not_found')
            ], 404);
        }
        $attribute->update($data);
        activity()
            ->performedOn($attribute)
            ->causedBy($user)
            ->withProperties($data)
            ->log('Attribute updated');
        return $attribute;
    }
    public function delete($id, $user){
        try {
            $attribute = Attributes::findOrFail($id);


            // Attribute'ün ilişkili değerleri var mı kontrol et
            if ($attribute->values()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.attribute_has_values')
                ], 422);
            }

            $attribute->delete();
            activity()
                //->performedOn($attribute)
                ->causedBy($user)
                ->log('Attribute deleted');
            return $attribute;
        } catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.attribute_not_found')
            ], 404);
        }
    }
}