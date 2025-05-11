<?php

namespace App\Http\Controllers\Ecommerce\Sale;

use App\Models\Sale\Sale;
use App\Mail\Sale\SaleMail;
use Illuminate\Http\Request;
use App\Models\Cart\CartShop;
use App\Models\Sale\SaleDetail;
use App\Models\Sale\SaleAddress;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class SaleController extends Controller
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

        $sale = Sale::create($request->sale);
        //
        $sale_address = $request->sale_address;
        $sale_address["sale_id"] = $sale->id;
        $sale_address = SaleAddress::create($sale_address);

        //CARRITO DE COMPRA O DETALLE DE VENTA

        $cartshop = CartShop::where("user_id",auth('api')->user()->id)->get();

        foreach ($cartshop as $key => $cart) {
            // $cart->delete();
            $sale_detail = $cart->toArray();
            $sale_detail["sale_id"] =  $sale->id;
            SaleDetail::create($sale_detail);
        }
        Mail::to($sale->user->email)->send(new SaleMail($sale));
        return response()->json(["message" => 200,"message_text" => "LA VENTA SE EFECTUO DE MANERA CORRECTA"]);
    }

    public function send_email($id)
    {
        $sale = Sale::findOrFail($id);
        Mail::to("echodeveloper960@gmail.com")->send(new SaleMail($sale));
        return "TODO SALIO BIEN";
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
        //
    }
}
