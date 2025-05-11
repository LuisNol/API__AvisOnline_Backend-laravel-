<table>
    <tr>
        <td colspan="4">VENTAS REALIZADAS</td>
    </tr>

    <thead>
        <tr>
             {{-- NO IMPORTANTE PUEDES USAR CODIGO HEXADECIMAL MAS NO RGBA PORQUE NO LO LEE --}}
            <th>#</th>
            <th width="30" style="background: blue;">N° Transacción</th>
            <th width="30" style="background: #c84949;">Metodo de pago</th>
            <th width="30" style="background: blue;">Fecha</th>
            <th width="30" style="background: blue;">Tipo de Moneda</th>
            <th width="30" style="background: #dddd;">Cliente</th>
            <th width="30" style="background: blue;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sales as $key => $sale)
            <tr>
                <td>{{ $key+1 }}</td>
                <td>{{ $sale->n_transaccion }}</td>
                <td>{{ $sale->method_payment }}</td>
                <td>{{ $sale->created_at->format("Y/m/d") }}</td>
                <td>{{ $sale->currency_payment }}</td>
                <td>{{ $sale->user->name . ' ' .$sale->user->surname }}</td>
                <td>{{ $sale->total }}</td>
            </tr>
        @endforeach
    </tbody>
</table>