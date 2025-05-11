<?php

namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportSaleBlade implements FromView
{
    protected $sales;
    function __construct($sales) {
        $this->sales = $sales;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        return view("sale.sale_excel",[
            "sales" => $this->sales,
        ]);
    }
}
