<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Products;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{

    //products list
    public function index(){
        $products=Products::with([
            'brands',
            'categories',
            'variants',
            'variants.attributeValue',
            'variants.qrCodes',
            'productImage',
        ])->get();

        return response()->json([
            'success'=>true,
            'message'=>__('messages.products_listed'),
            'data'=> $products
        ]);
    }


    //products add
    public function store(Request $request){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage products')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized'),
            ],401);
        }
        $validatedData = $request->validate([
            'sku' => 'required|string|unique:products,sku',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'brand_id' => 'required|exists:brands,id',
            'is_active' => 'sometimes|boolean',
            'slug'=> 'nullable|string|unique:products,slug',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id'
        ]);
        $productData = collect($validatedData)->except(['category_ids'])->toArray();
        $product=Products::create($productData);

        if(isset($validatedData['category_ids'])){
            $product->categories()->sync($validatedData['category_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.product_created'),
            'data' => $product->load(['brands', 'categories', 'variants', 'productImage']),
        ], Response::HTTP_CREATED);
    }
    //desired product
    public function show($id){
        $products=Products::with([
            'brands',
            'categories',
            'variants',
            'productImage',
        ])->findOrFail($id);
        return response()->json([
            'success' => true,
            'message' => __('messages.product_listed'),
            'data' => $products
        ]);
    }

    //update product
    public function update(Request $request, $id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage products')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ],401);
        }


        $product=Products::findOrFail($id);
        $validatedData=$request->validate([
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'name' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric',
            'brand_id' => 'sometimes|required|exists:brands,id',
            'is_active' => 'sometimes|boolean',
            'slug'=> 'nullable|string|unique:products,slug',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id'
        ]);

        $productData=collect($validatedData)->except(['category_ids'])->toArray();

        $product->update($productData);

        //Sync categories
        if(array_key_exists('category_ids', $validatedData)){
            $product->categories()->sync($validatedData['category_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.product_updated'),
            'data' => $product->load(['brands', 'categories', 'variants', 'productImage']),
        ], Response::HTTP_OK);
    }

    //product delete
    public function destroy(Request $request,$id){
        $user=$request->user();
        if(!$user->hasPermissionTo('manage products')){
            return response()->json([
                'success'=>false,
                'message'=>__('messages.unauthorized')
            ],401);
        }

        $product=Products::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.product_deleted')
        ], Response::HTTP_NO_CONTENT);

    }


}