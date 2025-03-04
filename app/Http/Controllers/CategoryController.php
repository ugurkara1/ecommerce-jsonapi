<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryController extends Controller
{

    //category list
    /*public function index(Request $request){
        try{
            $parentId = $request->input('parent_id');
            $withChildren=$request->boolean('with_children',false);
            $withProducts=$request->boolean('with_products',false);

            $query=Categories::query();
            if($parentId===null){
                $query->whereNull('parent_category_id');
            }elseif($parentId){
                $query->where('parent_category_id',$parentId);
            }

            if($withChildren){
                $query->with('children');
            }
            if($withProducts){
                $query->with('products');
            }
            $categories = $query->orderBy('name')->paginate($request->input('per_page', 15));
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Kategoriler listelenirken hata oluştu: ' . $e->getMessage()
            ], 500);
        }
    }*/

    public function getCategory(){
        try{
            // Parent kategorileri al, her biri için alt kategoriler ile birlikte

            $categories=Categories::whereNull("parent_category_id")->with(['children'=>function($query){//parent kategoriler
                $query->orderBy('name');//çocuk kategoriler sıralama

            }])
            ->orderBy('name') //parent kategoriler sıralama
            ->get();
            //eğer kategori yoksa
            if($categories->isEmpty()){
                return response()->json([
                    'success'=>false,
                    'message'=> __('messages.category_not_found')

                ],404);
            }
            return response()->json([
                'success' => true,
                'message' => __('messages.category_list'),
                'data' => $categories,
            ], 200);


        }catch(\Exception $e){
            return response()->json([
                'success'=> false,
                'message'=> $e->getMessage()
            ],500);
        }
    }
    public function index(Request $request){
        try{
            $category=Categories::all();
            return response()->json([
                'success'=>true,
                'message'=>__('messages.category_list'),
                'data'=>$category,
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'success'=> false,
                'message' => __('messages.categories_retrieval_error') . $e->getMessage(),
            ],500);
        }
    }
    //category add
    public function create(Request $request){
        $user=$request->user();
        if(!$user->hasPermissionTo("manage categories")){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
            ]);
        }
        //validasyon
        $validator=Validator::make($request->all(),[
            'name'=> 'required|string|max:255|unique:categories,name',
            'parent_category_id'=>'nullable|exists:categories,id',
            'slug'=> 'nullable|string|unique:categories,slug',
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'errors'=>$validator->errors(),
                'message'=>__('messages.invalid_data'),
            ],422);
        }
        try{
            $slug=$request->slug ?? Str::slug($request->name);
            $category=Categories::create([
                'name'=> $request->name,
                'slug'=> $slug,
                'parent_category_id'=>$request->parent_category_id
            ]);
            return response()->json([
                'success'=>true,
                'message'=>__('messages.category_created'),
                'data'=> $category,
            ],201);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error'),
                'error'=>$e->getMessage(),
            ],500);
        }
    }
    //category delete
    public function destroy(Request $request,$id){

        $user=$request->user();
        if(!$user->hasPermissionTo('manage categories')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
            ],403);
        }
        try{
            $categories=Categories::where('id',$id)->first();
            //alt kategorisi olan bir kategori silinemez
            if($categories->children()->exists()){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.category_has_children'),
                ],403);
            }
            if($categories->products()->exists()){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.category_has_products'),
                ],403);
            }
            $categoryData=[
                'id'=>$categories->id,
                'name'=> $categories->name,
                'slug'=> $categories->slug,
            ];
            $functionName=__FUNCTION__;
            activity()
                ->causedBy($user)
                ->performedOn($categories)
                ->withProperties(['attributes'=>$categoryData])
                ->log("Function Name : $functionName Category deleted successfully");

            $categories->delete();

            return response()->json([
                'success'=>true,
                'message'=>__('messages.category_deleted'),
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error'),
                'error'=>$e->getMessage(),
            ],500);
        }

    }
    //category update
    public function update(Request $request, $id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage categories')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
            ],403);
        }
        $category=Categories::where('id',$id)->first();
        if(!$category){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.category_not_found'),
            ],404);
        }
        $validator=Validator::make($request->all(),[
            'name'=> 'required|string|max:255|unique:categories,name,'.$id,
            'parent_category_id'=>'nullable|exists:categories,id',
            'slug'=> 'nullable|string|unique:categories,slug,'.$id,
        ]);
        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'errors'=>$validator->errors(),
                'message'=>__('messages.invalid_data'),
            ],422);
        }
        try{
            $slug=$request->slug ?? Str::slug($request->name);
            $category->update([
                'name'=> $request->name,
                'slug'=> $slug,
                'parent_category_id'=>$request->parent_category_id
            ]);
            return response()->json([
                'success'=>true,
                'message'=>__('messages.category_updated'),
                'data'=> $category,
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error'),
                'error'=>$e->getMessage(),
            ],500);
        }
    }
    public function show(Request $request){
        try{
            $category=QueryBuilder::for(Categories::class)
                ->allowedFilters([
                    AllowedFilter::exact('id'),
                    AllowedFilter::partial('name'),
                    AllowedFilter::partial('slug'),
                ])
                ->get();
            if($category->isEmpty()){
                return response()->json([
                    'success'=>false,
                    'message'=>__('messages.category_not_found'),
                ],404);
            }
            return response()->json([
                'success'=>true,
                'data'=>$category,
            ],200);
        }catch(\Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.service_error'),
                'error'=>$e->getMessage(),
            ],500);
        }
    }
}