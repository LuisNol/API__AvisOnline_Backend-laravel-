<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportSale implements FromCollection, WithHeadings
{
    protected $sales;
    function __construct($sales) {
        $this->sales = $sales;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->sales->map(function($sale){
            return [
                "n_transaccion" => $sale->n_transaccion,
                "method_payment" => $sale->method_payment,
                "created_at" => $sale->created_at->format("Y/m/d"),
                "currency_payment" => $sale->currency_payment,
                "client" => $sale->user->name . ' ' . $sale->user->surname,
                "total" => $sale->total,
            ];
        });
    }

    public function headings() : array {
        return [
            "N TRANSACCIÓN",
            "METODO DE PAGO",
            "FECHA DE REGISTRO",
            "TIPO DE MONEDA",
            "CLIENTE",
            "TOTAL"
        ];
    }
}
