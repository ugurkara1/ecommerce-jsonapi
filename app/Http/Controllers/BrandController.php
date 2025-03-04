<?php

namespace App\Http\Controllers;

use App\Models\Brands;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

class BrandController extends Controller
{
    /*

    public function index(Request $request){
        try{
            $brands=Cache::remember('brand_list',60,function(){
                return Brands::select('id','name','logo_url')->get();
            });

            return response()->json([
                'success'=> true,
                'message'=>'Markalar başarıyla listelendi',
                'data'=> $brands
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Bir hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    */


    //marka add
    public function create(Request $request){
        //Yetki
        $user=$request->user();
        if(!$user->hasPermissionTo("manage brands")){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.unauthorized')
            ],403);
        }
        //Validasyon
        $validator=Validator::make($request->all(), [
            'name'=> 'required|string|max:255|unique:brands,name',
            'logo_url'=>'nullable|url|max:255'
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.invalid_data'),
                'errors'=> $validator->errors()
            ],422);
        }
        $validated=$validator->validated();
        try{
            $brand = Brands::create([
                'name' => $request->name,
                'logo_url' => $request->logo_url
            ]);
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($brand)
                ->withProperties(['attributes'=>$validated])
                ->log("Function name: $functionName Brand created successfully");
            return response()->json([
                'success'=>true,
                'message'=> __('messages.brand_created'),
                'data'=> $brand
            ],201);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.brand_not_created') .$e->getMessage()
            ] ,500);
        }
    }
    //Tüm markaları listeleme
    public function getBrands(Request $request){
        $user=$request->user();
        /*if(!$user->hasPermissionTo('manage brands')){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.unauthorized')
            ],403);
        }*/
        try{
            $brands = Brands::all();
            return response()->json([
                'success'=>true,
                'message'=> __('messages.brands_listed'),
                'data'=> $brands
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=> __('messages.service_error') .$e->getMessage()
            ],500);
        }
    }
    //Belirli markayı gösterme spatie/query ile
    public function show(Request $request){
        $user = $request->user();
        /*if (!$user->hasPermissionTo('manage brands')) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized')
            ], 403);
        }*/

        try {
            $brands = QueryBuilder::for(Brands::class)
                ->allowedFilters([
                    AllowedFilter::partial('name') // Eğer burada hata alıyorsan, 'name' sütununun veritabanında olup olmadığını kontrol et.
                ])
                ->get();

            if ($brands->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.no_brands_found')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $brands
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error') . $e->getMessage()
            ], 500);
        }

    }
    //Marka güncelleme
    public function update(Request $request,$id){
        $user= $request->user();
        if(!$user->hasPermissionTo('manage brands')){
            return response()->json([
                'success'=> false,
                'message'=> __('messages.unauthorized')
            ],403);
        }
        $validator= Validator::make($request->all(), [
            'name'=>'required|string|max:255|unique:brands,name',
            'logo_url'=> 'nullable|url|max:255',
        ]);
        if( $validator->fails() ){
            return response()->json([
                'success'=> false,
                'messages'=>__('messages.invalid_data'),
                'errors'=>$validator->errors()
            ],422);
        }
        $validated=$validator->validated();
        try{

            $brands=Brands::where('id',$id)->first();
            $brands->update($request->only(['name','logo_url']));
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($brands)
                ->withProperties(['attributes'=>$validated])
                ->log("Function Name : $functionName Brand updated successfully ");
            return response()->json([
                'success'=> true,
                'message'=> __('messages.brand_updated'),
                'data'=> $brands
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success'=> false,
                'message'=>__('messages.service_error') . $e->getMessage()
            ],500);
        }
    }
    public function destroy(Request $request,$id){
        $user= $request->user();
        if(!$user->hasPermissionTo('manage brands')){
            return response()->json([
                'success'=> false,
                'message'=> __('messages.unauthorized')
            ],403);
        }
        try{
            $brands=Brands::where('id',$id)->first();
            if($brands->products()->exists()){
                return response()->json([
                    'success'=> false,
                    'message'=> __('messages.brand_has_products')
                ],422);
            }
            $brandsData=[
                'id'=> $brands->id,
                'name'=> $brands->name,
                'logo_url'=> $brands->logo_url,
            ];
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($brands)
                ->withProperties(['attributes'=>$brandsData])
                ->log("Function Name: $functionName.Brands deleted successfully.");
            $brands->delete();
            return response()->json([
                'success'=> true,
                'data'=> $brands,
                'messages'=>__('messages.brand_deleted')
            ]);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.service_error') . $e->getMessage()
            ], 500);
        }
    }
}