<?php

namespace App\Http\Controllers\Ecommerce\Client;

use Illuminate\Http\Request;
use App\Models\Client\AddressUser;
use App\Http\Controllers\Controller;

class AddressUserController extends Controller
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
        $address = AddressUser::where("user_id",auth('api')->user()->id)->orderBy("id","desc")->get();
        return response()->json(["address" => $address]);
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
        $request->request->add(["user_id" => auth("api")->user()->id]);
        $address = AddressUser::create($request->all());
        return response()->json(["message" => 200,"address" => $address]);
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
        $address = AddressUser::findOrFail($id);
        $address->update($request->all());
        return response()->json(["message" => 200,"address" => $address]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $address = AddressUser::findOrFail($id);
        $address->delete();
        return response()->json(["message" => 200]);
    }
}
