<?php

namespace App\Http\Controllers\Cupones;

use App\Models\Cupon\Cupone;
use Illuminate\Http\Request;
use App\Models\Product\Product;
use App\Models\Product\Categorie;
use App\Http\Controllers\Controller;

class CuponesController extends Controller
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
        $cupones = Cupone::where("code","like","%".$request->search."%")->orderBy("id","desc")->get();
        return response()->json(["message" => 200,
            "cupones" => $cupones,
        ]);
    }

    public function config_all()
    {
        $products = Product::where("state",2)->orderBy("id","desc")->get();
        $categories = Categorie::orderBy("id","desc")->get();
        return response()->json(["message" => 200,
            "categories" => $categories,
            "products" => $products,
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $IS_VALID = Cupone::where("code", $request->code)->first();
        if($IS_VALID){
            return response()->json(["message" => 403, "message_text" => "EL CODIGO DEL CUPON YA EXISTE"]);
        }

        if($request->type_cupon == 1){
            $products = [];
            foreach ($request->products_selected as $key => $product) {
                array_push($products,$product["id"]);
            }
            // [2,3,4] => 2,3,4
            $request->request->add(["products" => implode(",",$products)]);
        }
        if($request->type_cupon == 2){
            $categories = [];
            foreach ($request->categories_selected as $key => $categorie) {
                array_push($categories,$categorie["id"]);
            }
            $request->request->add(["categories" => implode(",",$categories)]);
        }
        Cupone::create($request->all());

        return response()->json(["message" => 200]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $cupone = Cupone::findOrFail($id);

        return response()->json(["message" => 200 , "cupone" => $cupone ]);
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
        $IS_VALID = Cupone::where("id","<>",$id)->where("code", $request->code)->first();
        if($IS_VALID){
            return response()->json(["message" => 403, "message_text" => "EL CODIGO DEL CUPON YA EXISTE"]);
        }

        if($request->type_cupon == 1){
            $products = [];
            foreach ($request->products_selected as $key => $product) {
                array_push($products,$product["id"]);
            }
            // [2,3,4] => 2,3,4
            $request->request->add(["products" => implode(",",$products)]);
        }
        if($request->type_cupon == 2){
            $categories = [];
            foreach ($request->categories_selected as $key => $categorie) {
                array_push($categories,$categorie["id"]);
            }
            $request->request->add(["categories" => implode(",",$categories)]);
        }
        $cupone = Cupone::findOrFail($id);
        $cupone->update($request->all());
        return response()->json(["message" => 200]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $cupone = Cupone::findOrFail($id);
        $cupone->delete();
        return response()->json(["message" => 200]);
    }
}
