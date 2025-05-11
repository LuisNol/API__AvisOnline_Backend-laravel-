<?php

namespace App\Http\Controllers\Ecommerce\Cart;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Sale\WishList\Wishlist;

class WishListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $wishlists = Wishlist::where("user_id",auth('api')->user()->id)->orderBy("id","desc")->get();

        return response()->json(["wishlists" => $wishlists->map(function($wishlist){
            return [
                "id" => $wishlist->id,
                "user" => [
                    "id" => $wishlist->client->id,
                    "name" =>$wishlist->client->name,
                ],
                "product_size_id" => $wishlist->product_size_id,
                "product_color_size_id" => $wishlist->product_color_size_id,
                "product" => [
                    "id" =>  $wishlist->product->id,
                    "title" => $wishlist->product->title,
                    "slug" => $wishlist->product->slug,
                    "price_soles" => $wishlist->product->price_soles,
                    "price_usd" => $wishlist->product->price_usd,
                    "imagen" =>  env("APP_URL")."storage/".$wishlist->product->imagen,
                ],
            ];
        })]
        );
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
        $validate_wishlist = Wishlist::where("product_id",$request->product_id)->first();
        if($validate_wishlist){
            return response()->json(["message" => 403, "message_text" => "EL PRODUCTO SELECCIONADO YA EXISTE"]);
        }
        $wishlist = Wishlist::create($request->all());
        return response(["message" => 200 , "wishlist" => [
            "id" => $wishlist->id,
            "user" => [
                "id" => $wishlist->client->id,
                "name" =>$wishlist->client->name,
            ],
            "product_size_id" => $wishlist->product_size_id,
            "product_color_size_id" => $wishlist->product_color_size_id,
            "product" => [
                "id" =>  $wishlist->product->id,
                "title" => $wishlist->product->title,
                "slug" => $wishlist->product->slug,
                "price_soles" => $wishlist->product->price_soles,
                "price_usd" => $wishlist->product->price_usd,
                "imagen" =>  env("APP_URL")."storage/".$wishlist->product->imagen,
            ],
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $wishlist = Wishlist::findOrFail($id);
        $wishlist->delete();
        return response()->json(["message" => 200]);
    }
}
