<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Factura / Venta #{{ $sale->id }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <style>
    body {
      font-family: 'Helvetica', 'Arial', sans-serif;
      font-size: 13px;
      margin: 25px;
      color: #333;
    }

    .header-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }

    .header-table td {
      vertical-align: middle;
      padding: 0;
    }

    .logo {
      height: 70px;
    }

    .header-info {
      text-align: right;
      font-size: 12px;
      color: #555;
    }

    .header-info strong {
      color: #000;
    }

    /* Separador elegante */
    .divider {
      border-top: 2px dashed #5ca94a;
      margin: 10px 0 15px 0;
    }

    /* Sección de información */
    .section-title {
      font-weight: bold;
      color: #43941d;
      text-transform: uppercase;
      font-size: 0.9em;
      margin-bottom: 3px;
    }

    .info-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85em;
    }

    .info-table td {
      padding: 3px 0;
      vertical-align: top;
    }

    /* Tabla de detalles */
    .line-items-container {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85em;
      margin-top: 10px;
    }

    .line-items-container th {
      text-align: left;
      background-color: #f0f0f0;
      color: #444;
      padding: 8px;
      border-bottom: 2px solid #ddd;
      text-transform: uppercase;
      font-size: 0.75em;
    }

    .line-items-container th:last-child,
    .line-items-container td:last-child {
      text-align: right;
    }

    .line-items-container td {
      padding: 6px 4px;
      border-bottom: 1px solid #eee;
    }

    /* Totales */
    .total {
      font-weight: bold;
      color: #43941d;
    }

    .payment-info {
      font-size: 0.85em;
      line-height: 1.4;
    }

    /* Footer */
    .footer {
      margin-top: 25px;
      text-align: center;
      font-size: 0.75em;
      color: #888;
      border-top: 1px solid #ccc;
      padding-top: 8px;
    }
  </style>
</head>

<body>
  <!-- ENCABEZADO -->
  <table class="header-table">
    <tr>
      <td>
        <img class="logo" src="{{ public_path('logoFARMACIA.png') }}">
      </td>
      <td class="header-info">
        <div><strong>N° {{ $sale->state_sale == 1 ? 'VENTA' : 'COTIZACIÓN' }}:</strong> #{{ $sale->id }}</div>
        <div>Fecha: <strong>{{ $sale->created_at->format('Y/m/d') }}</strong></div>
        <div>farmaciaesperanza@esperanza.com</div>
      </td>
    </tr>
  </table>

  <div class="divider"></div>

  <!-- INFORMACIÓN DEL CLIENTE -->
  <div class="section-title">Datos del cliente</div>
  <table class="info-table">
    <tr>
      <td><strong>Cliente:</strong> {{ $sale->client->full_name }}</td>
      <td><strong>Teléfono:</strong> {{ $sale->client->phone }}</td>
    </tr>
    <tr>
      <td><strong>Dirección:</strong> {{ $sale->client->address }}</td>
      <td><strong>{{ $sale->client->type_document }}:</strong> {{ $sale->client->n_document }}</td>
    </tr>
    <tr>
      <td colspan="2"><strong>Tipo de cliente:</strong> {{ $sale->client->type_client == 1 ? 'CLIENTE FINAL' : 'CLIENTE EMPRESA' }}</td>
    </tr>
  </table>

  <div class="divider"></div>

  <!-- INFORMACIÓN DE SUCURSAL -->
  <div class="section-title">Sucursal de atención</div>
  <table class="info-table">
    <tr>
      <td><strong>Sucursal:</strong> {{ $sale->sucursale->name }}</td>
      <td><strong>Dirección:</strong> {{ $sale->sucursale->address }}</td>
    </tr>
    <tr>
      <td><strong>Vendedor:</strong> {{ $sale->user->name . ' ' . $sale->user->surname }}</td>
      <td><strong>Teléfono:</strong> {{ $sale->user->phone }}</td>
    </tr>
  </table>

  <div class="divider"></div>

  <!-- DETALLES DE PRODUCTOS -->
  <table class="line-items-container">
    <thead>
      <tr>
        <th>Cant.</th>
        <th>Descripción</th>
        <th>Subtotal</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($sale->sale_details as $sale_detail)
        <tr>
          <td>{{ $sale_detail->quantity }}</td>
          <td>
            <strong>{{ $sale_detail->product?->title ?? '---' }}</strong><br>
            <small>Categoría:</small> {{ $sale_detail->product?->product_categorie?->title ?? '---' }}<br>
            <small>Descripción:</small> {{ $sale_detail->description ?? '---' }}
          </td>
          <td>{{ number_format($sale_detail->subtotal, 2) }} $</td>
          <td class="total">{{ number_format($sale_detail->total, 2) }} $</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="divider"></div>

  <!-- INFORMACIÓN DE PAGO -->
  <table class="info-table">
    <tr>
      <td class="payment-info">
        <strong>Método de pago:</strong>
        @if ($sale->first_payment)
          {{ $sale->first_payment->method_payment }} ({{ $sale->first_payment->amount }} $)
        @else
          No especificado
        @endif
      </td>
      <td class="payment-info right">
        <strong>Fecha de entrega:</strong>
        {{ $sale->date_validation ? Carbon\Carbon::parse($sale->date_validation)->format('Y/m/d h:i A') : 'Pendiente' }}
      </td>
    </tr>
    <tr>
      <td colspan="2" class="right">
        <div><strong>Total:</strong> {{ number_format($sale->total, 2) }} $</div>
        <div><strong>Descuento:</strong> -{{ number_format($sale->discount, 2) }} $</div>
        <div><strong>Adelantado:</strong> {{ number_format($sale->paid_out, 2) }} $</div>
        <div><strong>Saldo:</strong> {{ number_format($sale->debt, 2) }} $</div>
      </td>
    </tr>
  </table>

  <!-- PIE DE PÁGINA -->
  <div class="footer">
    Gracias por su compra.  
    <br>
    Farmacia Esperanza © {{ date('Y') }} — Todos los derechos reservados.
  </div>
</body>
</html>
