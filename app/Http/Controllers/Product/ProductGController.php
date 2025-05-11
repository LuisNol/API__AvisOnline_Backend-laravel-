<?php

namespace App\Http\Controllers\Product;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Product\Product;
use App\Models\Product\Categorie;
use App\Models\Product\ProductSize;
use App\Http\Controllers\Controller;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductImage;
use Illuminate\Support\Facades\Storage;
use App\Models\Product\ProductColorSize;
use App\Http\Resources\Product\ProductCResource;
use App\Http\Resources\Product\ProductCCollection;

class ProductGController extends Controller
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
    public function index(Request $request)
    {
        $search = $request->search;
        $categorie_id = $request->categorie_id;
        $products = Product::filterProduct($search,$categorie_id)->orderBy("id","desc")->paginate(30);

        return response()->json([
            "message" => 200,
            "total" => $products->total(),
            "products" => ProductCCollection::make($products),
        ]);
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

    public function get_info()
    {
       $categories = Categorie::orderBy("id","desc")->get();

       $products_colors = ProductColor::orderBy("id","desc")->get();

       $products_color_sizes = ProductSize::orderBy("id","desc")->get();

       return response()->json(["categories" => $categories, "products_colors" => $products_colors , "products_color_sizes" => $products_color_sizes]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $is_product = Product::where("title",$request->title)->first();
        if($is_product){
            return response()->json(["message" => 403]);
        }

        // $request->request->add(["tags" => implode(",",$request->tags_e)]);
        $request->request->add(["slug" => Str::slug($request->title)]);
        if($request->hasFile("imagen_file")){
            $path = Storage::putFile("productos",$request->file("imagen_file"));
            $request->request->add(["imagen" => $path]);
        }
        $product = Product::create($request->all());

        foreach ($request->file("files") as $key => $file) {
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();
            $nombre = $file->getClientOriginalName();

            $path = Storage::putFile("productos",$file);
            ProductImage::create([
                "product_id" => $product->id,
                "file_name" => $nombre,
                "imagen" => $path,
                "size" => $size,
                "type" => $extension,
            ]);
        }

        return response()->json(["message" => 200 ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            "product" => ProductCResource::make($product),
        ]);
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
    public function update(Request $request, $id)
    {
        $is_product = Product::where("id","<>",$id)->where("title",$request->title)->first();
        if($is_product){
            return response()->json(["message" => 403]);
        }

        $product = Product::findOrFail($id);

        // $request->request->add(["tags" => implode(",",$request->tags_e)]);
        $request->request->add(["slug" => Str::slug($request->title)]);
        if($request->hasFile("imagen_file")){
            $path = Storage::putFile("productos",$request->file("imagen_file"));
            $request->request->add(["imagen" => $path]);
        }
        $product->update($request->all());

        return response()->json(["message" => 200 ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
