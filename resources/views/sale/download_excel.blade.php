<table>
    <thead>
        <tr>
            <th>N°</th>
            <th width="35">Cliente</th>
            <th width="20">Tipo de Cliente</th>
            <th width="30">Asesor</th>
            <th width="15">Sucursal</th>
            <th width="15">Descuento</th>
            <th width="15">Subtotal</th>
            <th width="15">Igv</th>
            <th width="15">Total</th>
            <th width="15">Deuda</th>
            <th width="15">Pagado</th>
            <th width="20">Tipo</th>
            <th width="20">Estado de pago</th>
            <th width="20">Estado Entrega</th>
            <th width="25">Fecha de registro</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sales as $sale)
            <tr>
                <td>{{$sale->id}}</td>
                <td>{{$sale->client->full_name}}</td>
                <td> {{$sale->type_client == 1 ? 'CLIENTE FINAL' : 'CLIENTE EMPRESA'}} </td>
                <td> {{$sale->user->name.' '.$sale->user->surname}} </td>
                <td> {{$sale->sucursale->name}} </td>
                <td> S/. {{$sale->discount}} </td>
                <td> S/. {{$sale->subtotal}} </td>
                <td> S/. {{$sale->igv}} </td>
                <td> S/. {{$sale->total}} </td>
                <td> S/. {{$sale->debt}} </td>
                <td> S/. {{$sale->paid_out}} </td>
                <td> {{$sale->type == 1 ? 'VENTA' : 'COTIZACIÓN'}} </td>
                {{-- ESTADO DE PAGO  --}}
                @if ($sale->state_payment == 1)
                    <td style="background:#ffa09e"> PENDIENTE </td>
                @endif
                @if ($sale->state_payment == 2)
                    <td style="background:#fff5aa"> PARCIAL </td>
                @endif
                @if ($sale->state_payment == 3)
                    <td style="background:#abc6ff"> COMPLETO </td>
                @endif
                {{--  --}}
                {{-- ESTADO DE ENTREGA  --}}
                @if ($sale->state_entrega == 1)
                    <td style="background:#ffc38f"> PENDIENTE </td>
                @endif
                @if ($sale->state_entrega == 2)
                    <td style="background:#b1eaff"> PARCIAL </td>
                @endif
                @if ($sale->state_entrega == 3)
                    <td style="background:#cfffab"> COMPLETO </td>
                @endif
                {{--  --}}
                <td> {{$sale->created_at->format("Y/m/d h:i A")}} </td>
            </tr>
        @endforeach
    </tbody>
</table>