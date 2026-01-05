<table>
    <thead>
        <tr>
            <th width="40">Producto</th>
            <th  width="25">SKU</th>
            <th  width="25">Precio - Cliente Final</th>
            <th  width="25">Precio - Cliente Empresa</th>
            <th  width="25">Categoria</th>
            <th  width="25">¿Es regalo?</th>
            <th  width="25">¿Descuento?</th>
            <th  width="25">Tipo de Impuesto</th>
            <th  width="25">Importe Iva</th>
            <th  width="25">Disponibilidad</th>
            <th  width="20">Estado</th>
            <th  width="30">Dias de garantia</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($list_products as $list_product)
            <tr>
                <td>
                    {{$list_product->title}}
                </td>
                <td>
                    {{$list_product->sku}}
                </td>
                <td>
                    {{$list_product->price_general}}
                </td>
                <td>
                    {{$list_product->price_company}}
                </td>
                <td>
                    {{$list_product->product_categorie->title?? 'Sin categoría'}}
                </td>
                <td>
                    {{$list_product->is_gift == 1 ? 'NO' : 'SI'}}
                </td>
                <td>
                    {{$list_product->is_discount == 1 ? 'NO' : 'SI'}}
                    <br>
                    Descuento: {{$list_product->max_discount}} %
                </td>
                <td>
                    @php
                        $tax_name = '';
                    @endphp
                    @switch($list_product->tax_selected)
                        @case(1)
                            {{$tax_name = "Sujeto a Impuesto";}}
                            @break
                        @case(2)
                            {{$tax_name = "Libre de Impuesto";}}
                            @break
                        @default
                            
                    @endswitch
                    {{$tax_name}}
                </td>
                <td>
                    {{$list_product->importe_iva}} %
                </td>
                <td>
                    @php
                        $disponibilida_name = '';
                    @endphp
                    @switch($list_product->disponibilidad)
                        @case(1)
                            {{$disponibilida_name = "Vender sin Stock";}}
                            @break
                        @case(2)
                            {{$disponibilida_name = "No Vender sin Stock";}}
                            @break
                        @default
                            
                    @endswitch
                    {{$disponibilida_name}}
                </td>

                @if ($list_product->state == 1)
                    <td style="background: #b6c8ff">
                        ACTIVO
                    </td>
                    @endif
                @if ($list_product->state == 2)
                <td style="background: #f7a6a6;">
                    INACTIVO
                </td>
                @endif

                <td>
                    {{$list_product->warranty_day}} dias
                </td>
            </tr>
        @endforeach

    </tbody>
</table>