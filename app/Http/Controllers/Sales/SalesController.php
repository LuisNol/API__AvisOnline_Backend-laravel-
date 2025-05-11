<?php

namespace App\Http\Controllers\Sales;

use App\Models\Sale\Sale;
use App\Exports\ExportSale;
use Illuminate\Http\Request;
use App\Exports\ExportSaleBlade;
use App\Models\Product\Categorie;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\Ecommerce\Sale\SaleOCollection;

class SalesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api',["except" => ["sale_all_export"]]);
    }

    public function sale_all(Request $request)
    {
        $search = $request->search;
        $categorie_id = $request->categorie_id;
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $orders = Sale::filterAdvance($search,$categorie_id,$start_date,$end_date)->orderBy("id","desc")->get();
        $categories = Categorie::orderBy("id","desc")->get();
        return response()->json(["categories" => $categories,"orders" => SaleOCollection::make($orders)]);
    }

    function sale_all_export(Request $request) {
        
        $search = $request->search;
        $categorie_id = $request->categorie_id;
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $orders = Sale::filterAdvance($search,$categorie_id,$start_date,$end_date)->orderBy("id","desc")->get();

        return Excel::download(new ExportSaleBlade($orders), 'sales_exports.xlsx');
    }
}
