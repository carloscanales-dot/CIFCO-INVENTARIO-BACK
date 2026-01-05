<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Solicitud de Compra</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="{{ public_path('favicon.ico') }}">

  <style>
    body {
      font-family: 'Helvetica', 'Arial', sans-serif;
      font-size: 12px;
      color: #333;
      margin: 25px;
    }

    h1 {
      color: #2f6d1b;
      margin: 0;
      font-size: 1.8em;
      letter-spacing: 1px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    td, th {
      padding: 6px 4px;
      vertical-align: top;
    }

    th {
      text-transform: uppercase;
      background: #e8f2e2;
      color: #2f6d1b;
      border-bottom: 2px solid #b5d6a8;
      font-size: 0.8em;
    }

    .header-logo {
      height: 70px;
    }

    .divider {
      border-top: 2px dashed #5ca94a;
      margin: 10px 0;
    }

    .section-title {
      background: #e6efe0;
      padding: 6px;
      font-weight: bold;
      color: #2f6d1b;
      font-size: 0.85em;
      margin-top: 10px;
    }

    .info-table td {
      padding: 3px 0;
      font-size: 0.85em;
    }

    .info-table strong {
      color: #000;
    }

    .table-products th {
      text-align: left;
    }

    .table-products th:last-child,
    .table-products td:last-child {
      text-align: right;
    }

    .table-products td {
      border-bottom: 1px solid #eee;
    }

    .totals-table td {
      font-size: 0.9em;
    }

    .totals-table tr td:last-child {
      text-align: right;
    }

    .totals-table strong {
      color: #2f6d1b;
    }

    .footer {
      text-align: center;
      margin-top: 60px;
      font-size: 0.8em;
      color: #666;
    }

    .signature {
      text-align: center;
      padding-top: 40px;
    }

    .signature-line {
      border-top: 1px solid #999;
      width: 70%;
      margin: 10px auto 5px auto;
    }

    .signature small {
      color: #444;
      font-size: 0.8em;
    }

  </style>
</head>

<body>

  <!-- ENCABEZADO -->
  <table>
    <tr>
      <td>
        <img src="{{ public_path('logoFARMACIA.png') }}" class="header-logo">
      </td>
      <td style="text-align:right;">
        <h1>ORDEN DE COMPRA</h1>
        <div style="font-size: 0.85em; margin-top:5px;">
          <strong>Fecha emisión:</strong> {{ $purchase->date_emision_format }}<br>
          <strong>OC-NUM:</strong> {{ $purchase->created_at->format("Y") }} - {{ $purchase->id }}
        </div>
      </td>
    </tr>
  </table>

  <div class="divider"></div>

  <!-- DATOS GENERALES -->
  <table class="info-table">
    <tr>
      <td><strong>NIT:</strong> 85965421784</td>
      <td></td>
    </tr>
  </table>

  <!-- DATOS DEL PROVEEDOR -->
  <div class="section-title">1. Datos del Proveedor</div>
  <table class="info-table">
    <tr>
      <td><strong>Señor(es):</strong> {{ $purchase->provider->full_name }}</td>
      <td><strong>N° de solicitud:</strong> #{{ $purchase->id }}</td>
    </tr>
    <tr>
      <td><strong>Dirección:</strong> {{ $purchase->provider->address }}</td>
      <td><strong>T/Pago:</strong> Transferencia</td>
    </tr>
    <tr>
      <td><strong>NIT:</strong> {{ $purchase->provider->ruc }}</td>
      <td><strong>Moneda:</strong> Dólares Americanos</td>
    </tr>
    <tr>
      <td><strong>Teléfono:</strong> {{ $purchase->provider->phone }}</td>
      <td></td>
    </tr>
  </table>

  <!-- ENTREGA -->
  <div class="section-title">2. Entrega</div>
  <table class="info-table">
    <tr>
      <td><strong>Dirección:</strong> {{ $purchase->warehouse->address }}</td>
      <td><strong>Almacén:</strong> {{ $purchase->warehouse->name }}</td>
    </tr>
    <tr>
      <td><strong>Fecha de entrega:</strong> {{ $purchase->created_at->addDays(3)->format("Y/m/d") }}</td>
      <td><strong>Teléfono:</strong> 989785454</td>
    </tr>
  </table>

  <!-- DETALLE DE PRODUCTOS -->
  <table class="table-products" style="margin-top:15px;">
    <thead>
      <tr>
        <th>#</th>
        <th>Producto</th>
        <th>Unidad</th>
        <th>P. Unit</th>
        <th>Cant.</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($purchase->purchase_details as $key => $purchase_detail)
      <tr>
        <td>{{ $key + 1 }}</td>
        <td>{{ $purchase_detail->product->title }}</td>
        <td>{{ $purchase_detail->unit->name }}</td>
        <td>${{ number_format($purchase_detail->price_unit, 2) }}</td>
        <td>{{ $purchase_detail->quantity }}</td>
        <td><strong>${{ number_format($purchase_detail->total, 2) }}</strong></td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- OBSERVACIONES Y TOTALES -->
  <table style="margin-top:15px;">
    <tr>
      <td style="width:65%;">
        <div class="section-title">Observaciones</div>
        <p style="font-size: 0.85em; text-align: justify; margin-top:5px;">
          {{ $purchase->description ?? 'Sin observaciones adicionales.' }}
        </p>
      </td>
      <td>
        <table class="totals-table">
          <tr>
            <td>Importe:</td>
            <td><strong>${{ number_format($purchase->importe, 2) }}</strong></td>
          </tr>
          <tr>
            <td>IVA 13%:</td>
            <td><strong>${{ number_format($purchase->igv, 2) }}</strong></td>
          </tr>
          <tr>
            <td>Importe Total:</td>
            <td><strong>${{ number_format($purchase->total, 2) }}</strong></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- FIRMAS -->
  <div class="signature" style="margin-top:60px;">
    <table>
      <tr>
        <td style="text-align:center;">
          <div class="signature-line"></div>
          <small>Preparado por</small><br>
          Área de Compras
        </td>
        <td style="text-align:center;">
          <div class="signature-line"></div>
          <small>Aprobado por</small>
        </td>
      </tr>
    </table>
  </div>

  <div class="footer">
    Sistema de Compras — Farmacia Esperanza © {{ date('Y') }}
  </div>

</body>
</html>
