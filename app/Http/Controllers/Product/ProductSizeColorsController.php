<?php

namespace App\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Models\Product\ProductSize;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductColorSize;

class ProductSizeColorsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(!$request->product_size_id){
            $product_size_color = ProductSize::where("name",$request->new_nombre)->first();
            if($product_size_color){
                return response()->json(["message" => 403 , "text_message" => "ESTE NOMBRE DE DIMENSIÓN YA EXISTE"]);
            }
            $product_size = ProductSize::create([
                "product_id" => $request->product_id,
                "name" => $request->new_nombre,
            ]);
        }else{
            $product_size = ProductSize::findOrFail($request->product_size_id);
        }


        $product_size_color = ProductColorSize::where("product_color_id",$request->product_color_id)->where("product_size_id",$product_size->id)->first();
        if($product_size_color){
            return response()->json(["message" => 403 , "text_message" => "ESTA CONFIGURACIÓN YA EXISTE"]);
        }

        $product_size_color = ProductColorSize::create([
            "product_color_id" => $request->product_color_id,
            "product_size_id" => $product_size->id,
            "stock" => $request->stock,
        ]);

        return response()->json(["message" => 200 , "product_size_color" => [
            "id" => $product_size->id,
            "name" => $product_size->name,
            "total" => $product_size->product_size_colors->sum("stock"),
            "variaciones" => $product_size->product_size_colors->map(function($var){
                return [
                    "id" => $var->id,
                    "product_color_id" => $var->product_color_id,
                    "product_color" => $var->product_color,
                    "stock" => $var->stock,
                ];
            }),
        ]]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update_size(Request $request, $id)
    {
        $product_size_color = ProductSize::where("id","<>",$id)->where("name",$request->name)->first();
        if($product_size_color){
            return response()->json(["message" => 403 , "text_message" => "ESTE NOMBRE DE DIMENSIÓN YA EXISTE"]);
        }
        $product_size = ProductSize::findOrFail($id);
        $product_size->update($request->all());

        return response()->json(["message" => 200, "product_size" => [
            "id" => $product_size->id,
            "name" => $product_size->name,
            "total" => $product_size->product_size_colors->sum("stock"),
            "variaciones" => $product_size->product_size_colors->map(function($var){
                return [
                    "id" => $var->id,
                    "product_color_id" => $var->product_color_id,
                    "product_color" => $var->product_color,
                    "stock" => $var->stock,
                ];
            }),
        ]]);
    }

    public function update(Request $request, $id)
    {
        $product_size_color = ProductColorSize::where("id","<>",$id)->where("product_color_id",$request->product_color_id)->where("product_size_id",$request->product_size_id)->first();
        if($product_size_color){
            return response()->json(["message" => 403 , "text_message" => "ESTA CONFIGURACIÓN YA EXISTE"]);
        }

        $product_color_size = ProductColorSize::findOrFail($id);

        $product_color_size->update($request->all());

        return response()->json([
            "message" => 200,
            "product_color_size" => [
                "id" => $product_color_size->id,
                "product_color_id" => $product_color_size->product_color_id,
                "product_color" => $product_color_size->product_color,
                "stock" => $product_color_size->stock,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy_size($id)
    {
        $product_size = ProductSize::findOrFail($id);
        $product_size->delete();
        return response()->json(["message" =>200]);
    }
    public function destroy($id)
    {
        $product_size = ProductColorSize::findOrFail($id);
        $product_size->delete();
        return response()->json(["message" =>200]);
    }
}
