<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante</title>
    <style>
        @page {
            margin: 10mm 12mm 10mm 12mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
        }

        .sheet {
            width: 100%;
        }

        .top-table,
        .customer-table,
        .detail-table,
        .summary-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 32%;
            padding-top: 2px;
        }

        .company-cell {
            width: 43%;
            padding-top: 48px;
            padding-right: 8px;
        }

        .voucher-cell {
            width: 25%;
            padding-top: 12px;
        }

        .logo {
            width: 100%;
            max-width: 205px;
            height: auto;
            display: block;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.05;
            margin: 0 0 6px 0;
        }

        .company-address {
            margin: 0;
            font-size: 10px;
            line-height: 1.35;
            text-transform: uppercase;
        }

        .voucher-box {
            border: 2px solid #111;
            text-align: center;
            padding: 18px 10px 14px;
            min-height: 98px;
            font-weight: 700;
        }

        .voucher-ruc {
            font-size: 13px;
            margin-bottom: 20px;
        }

        .voucher-title {
            font-size: 14px;
            line-height: 1.25;
            margin-bottom: 18px;
        }

        .voucher-number {
            font-size: 13px;
        }

        .customer-table {
            width: 52%;
            margin: 12px auto 8px;
        }

        .customer-table td {
            padding: 2px 0;
            font-size: 11px;
        }

        .customer-label {
            width: 26%;
            font-weight: 700;
            padding-right: 4px;
        }

        .detail-table {
            margin-top: 8px;
        }

        .detail-table thead th {
            background: #3e3e3e;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 8px 6px;
            text-align: center;
        }

        .detail-table tbody td {
            padding: 8px 6px;
            font-size: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-table .desc {
            padding-left: 16px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .summary-wrap {
            width: 100%;
            margin-top: 6px;
        }

        .summary-table {
            width: 36%;
            margin-left: auto;
            border-spacing: 0;
        }

        .summary-table td {
            padding: 4px 6px;
        }

        .summary-label {
            background: #f2f2f2;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }

        .summary-value {
            text-align: right;
            font-size: 11px;
        }

        .summary-total-label,
        .summary-total-value {
            border-top: 2px solid #111;
            padding-top: 8px;
        }

        .summary-total-label {
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }

        .summary-total-value {
            font-size: 18px;
            font-weight: 700;
            text-align: right;
        }

        .detraction-line {
            margin-top: 6px;
            text-align: right;
            font-size: 10px;
            font-weight: 700;
        }

        .separator {
            border-top: 2px solid #111;
            margin: 8px 0 10px;
        }

        .footer-table td {
            vertical-align: top;
        }

        .qr-cell {
            width: 17%;
        }

        .qr-image {
            width: 118px;
            height: 118px;
            display: block;
        }

        .qr-text {
            width: 83%;
            padding-top: 44px;
            padding-left: 8px;
            font-size: 10px;
            line-height: 1.2;
        }

        .qr-payload {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 2px;
            word-break: break-all;
        }

        .qr-caption {
            font-size: 10px;
        }

        .qr-caption a {
            color: #1749ff;
            font-weight: 700;
            text-decoration: none;
        }

        .page-space {
            height: 1px;
        }
    </style>
</head>
<body>
@php
    $voucherLabel = $voucherType === 'Factura' ? 'FACTURA DE VENTA ELECTRÓNICA' : 'BOLETA DE VENTA ELECTRÓNICA';
    $voucherLabelHtml = $voucherType === 'Factura' ? 'FACTURA DE VENTA<br>ELECTRÓNICA' : 'BOLETA DE VENTA<br>ELECTRÓNICA';
    $docLabel = $voucherType === 'Factura' ? 'RUC' : 'DNI';
    $customerLabel = $voucherType === 'Factura' ? 'RAZÓN SOCIAL' : 'NOMBRE';
    $displayDetails = $details->count() > 0 ? $details : collect([(object) [
        'product' => null,
        'quantity' => 1,
        'unit_price' => $total,
        'subtotal' => $total,
    ]]);

    $formatQty = function ($value) {
        $formatted = number_format((float) $value, 2, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    };

    $logoTag = $logoDataUri
        ? '<img class="logo" src="' . $logoDataUri . '" alt="Logo">'
        : '<div style="border:1px solid #ccc; padding:20px; text-align:center; font-weight:700;">SIN LOGO</div>';
    $companyAddressHtml = implode('<br>', array_map(function ($line) {
        return e($line);
    }, $companyAddressLines ?? []));
@endphp

<div class="sheet">
    <table class="top-table">
        <tr>
            <td class="logo-cell">
                {!! $logoTag !!}
            </td>
            <td class="company-cell">
                <p class="company-name">{{ $companyName }}</p>
                <p class="company-address">{!! $companyAddressHtml !!}</p>
            </td>
            <td class="voucher-cell">
                <div class="voucher-box">
                    <div class="voucher-ruc">R.U.C. N° {{ $companyRuc }}</div>
                    <div class="voucher-title">{!! $voucherLabelHtml !!}</div>
                    <div class="voucher-number">{{ $seriesNumber }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="customer-table">
        <tr>
            <td class="customer-label">{{ $customerLabel }}:</td>
            <td>{{ $clientName }}</td>
        </tr>
        <tr>
            <td class="customer-label">{{ $docLabel }}:</td>
            <td>{{ $clientDocument }}</td>
        </tr>
        <tr>
            <td class="customer-label">EMISIÓN:</td>
            <td>{{ $issueDate->format('Y-m-d - H:i:s') }}</td>
        </tr>
        <tr>
            <td class="customer-label">MONEDA:</td>
            <td>SOL (PEN)</td>
        </tr>
    </table>

    <table class="detail-table">
        <thead>
            <tr>
                <th style="width: 16%;">CANTIDAD</th>
                <th style="width: 44%;">CÓDIGO Y DESCRIPCIÓN</th>
                <th style="width: 20%;">PRECIO UNITARIO</th>
                <th style="width: 20%;">PRECIO TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($displayDetails as $detail)
                @php
                    $productName = optional($detail->product)->name ?? 'Venta general';
                    $quantity = $formatQty($detail->quantity);
                    $unitPrice = number_format((float) $detail->unit_price, 3, '.', '');
                    $lineTotal = number_format((float) $detail->subtotal, 2, '.', '');
                @endphp
                <tr>
                    <td class="text-center">{{ $quantity }} UNIDADES</td>
                    <td class="desc">{{ $productName }}</td>
                    <td class="text-right">{{ $unitPrice }}</td>
                    <td class="text-right">{{ $lineTotal }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-wrap">
        <table class="summary-table">
            <tr>
                <td class="summary-label">OP. GRAVADA</td>
                <td class="summary-value">{{ number_format($subtotal, 2, '.', '') }}</td>
            </tr>
            <tr>
                <td class="summary-label">IGV</td>
                <td class="summary-value">{{ number_format($igv, 2, '.', '') }}</td>
            </tr>
            @if($detraction)
                <tr>
                    <td class="summary-label">DETRACCIÓN</td>
                    <td class="summary-value">{{ number_format($detraction, 2, '.', '') }}</td>
                </tr>
            @endif
            <tr>
                <td class="summary-total-label">IMPORTE TOTAL (S/)</td>
                <td class="summary-total-value">{{ number_format($total, 2, '.', '') }}</td>
            </tr>
        </table>

        @if($detraction)
            <div class="detraction-line">OPERACIÓN SUJETA A DETRACCIÓN</div>
        @endif
    </div>

    <div class="separator"></div>

    <table class="footer-table">
        <tr>
            <td class="qr-cell">
                @if(!empty($qrDataUri))
                    <img class="qr-image" src="{{ $qrDataUri }}" alt="QR">
                @endif
            </td>
            <td class="qr-text">
                <div class="qr-payload">{{ $qrPayload }}</div>
                <div class="qr-caption">
                    Representación impresa de la {{ $voucherLabel }}. Consultar validez en
                    <a href="https://apisunat.com/buscar">apisunat.com/buscar</a>
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
