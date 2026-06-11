<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $voucherLabel ?? 'COMPROBANTE' }}</title>
    <style>
        @page {
            margin: 14mm 12mm 12mm 12mm;
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
        .info-table,
        .detail-table,
        .summary-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 28%;
            padding-right: 8px;
        }

        .company-cell {
            width: 46%;
            padding-right: 8px;
        }

        .voucher-cell {
            width: 26%;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }

        .company-address {
            margin: 0;
            font-size: 10px;
            line-height: 1.35;
        }

        .voucher-box {
            border: 2px solid #111;
            padding: 14px 10px;
            text-align: center;
            font-weight: 700;
            min-height: 98px;
        }

        .voucher-box .ruc {
            font-size: 13px;
            margin-bottom: 16px;
        }

        .voucher-box .title {
            font-size: 14px;
            line-height: 1.25;
            margin-bottom: 18px;
        }

        .voucher-box .number {
            font-size: 13px;
        }

        .customer-block {
            margin-top: 8px;
            width: 60%;
            margin-left: 0;
            margin-bottom: 10px;
        }

        .customer-block td {
            padding: 2px 0;
            font-size: 11px;
        }

        .label {
            font-weight: 700;
            padding-right: 4px;
        }

        .detail-table {
            margin-top: 8px;
        }

        .detail-table thead th {
            background: #3c3c3c;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 8px 6px;
            text-align: center;
        }

        .detail-table td {
            padding: 7px 6px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }

        .detail-table .desc {
            padding-left: 14px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .summary-wrap {
            width: 100%;
            margin-top: 0;
        }

        .summary-table {
            width: 46%;
            margin-left: auto;
        }

        .summary-table td {
            padding: 4px 6px;
            font-size: 11px;
            border-bottom: 1px solid #e6e6e6;
        }

        .summary-table .label-cell {
            font-weight: 700;
            text-align: center;
            background: #f3f3f3;
        }

        .summary-table .value-cell {
            text-align: right;
            width: 34%;
        }

        .total-row td {
            border-top: 2px solid #111;
            border-bottom: 0;
            padding-top: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .grand-total {
            font-size: 18px;
            font-weight: 700;
        }

        .detraction-note {
            font-size: 10px;
            margin-top: 8px;
            text-align: right;
            font-weight: 700;
        }

        .separator {
            border-top: 2px solid #111;
            margin: 8px 0 10px 0;
        }

        .footer-table td {
            vertical-align: top;
        }

        .qr-box {
            width: 26%;
        }

        .qr-text {
            width: 74%;
            padding-left: 10px;
            font-size: 10px;
            line-height: 1.25;
        }

        .qr-code {
            display: block;
            width: 100%;
            max-width: 125px;
            height: auto;
        }

        .qr-payload {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 3px;
            word-break: break-all;
        }

        .small-muted {
            color: #222;
        }

        .logo {
            width: 100%;
            max-width: 175px;
            height: auto;
        }
    </style>
</head>
<body>
@php
    $voucherLabel = $voucherType === 'Factura' ? 'FACTURA DE VENTA ELECTRÓNICA' : 'BOLETA DE VENTA ELECTRÓNICA';
    $docLabel = $voucherType === 'Factura' ? 'RUC' : 'DNI';
    $customerNameLabel = $voucherType === 'Factura' ? 'RAZÓN SOCIAL' : 'NOMBRE';
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
@endphp

<div class="sheet">
    <table class="top-table">
        <tr>
            <td class="logo-cell">
                {!! $logoTag !!}
            </td>
            <td class="company-cell">
                <p class="company-name">{{ $companyName }}</p>
                <p class="company-address">{{ $companyAddress }}</p>
            </td>
            <td class="voucher-cell">
                <div class="voucher-box">
                    <div class="ruc">R.U.C. N° {{ $companyRuc }}</div>
                    <div class="title">{{ $voucherLabel }}</div>
                    <div class="number">{{ $seriesNumber }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="customer-block">
        <tr>
            <td class="label">{{ $customerNameLabel }}:</td>
            <td>{{ $clientName }}</td>
        </tr>
        <tr>
            <td class="label">{{ $docLabel }}:</td>
            <td>{{ $clientDocument }}</td>
        </tr>
        <tr>
            <td class="label">EMISIÓN:</td>
            <td>{{ $issueDate->format('Y-m-d - H:i:s') }}</td>
        </tr>
        <tr>
            <td class="label">MONEDA:</td>
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
                <td class="label-cell">OP. GRAVADA</td>
                <td class="value-cell">{{ number_format($subtotal, 2, '.', '') }}</td>
            </tr>
            <tr>
                <td class="label-cell">IGV</td>
                <td class="value-cell">{{ number_format($igv, 2, '.', '') }}</td>
            </tr>
            @if($detraction)
                <tr>
                    <td class="label-cell">DETRACCIÓN</td>
                    <td class="value-cell">{{ number_format($detraction, 2, '.', '') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td class="label-cell">IMPORTE TOTAL (S/)</td>
                <td class="value-cell grand-total">{{ number_format($total, 2, '.', '') }}</td>
            </tr>
        </table>

        @if($detraction)
            <div class="detraction-note">
                OPERACIÓN SUJETA A DETRACCIÓN
            </div>
        @endif
    </div>

    <div class="separator"></div>

    <table class="footer-table">
        <tr>
            <td class="qr-box">
                <svg class="qr-code" viewBox="0 0 37 37" xmlns="http://www.w3.org/2000/svg" aria-label="QR">
                    <rect x="0" y="0" width="37" height="37" fill="#fff"/>
                    @php
                        $size = count($qrMatrix);
                        $offset = 4;
                    @endphp
                    @for ($y = 0; $y < $size; $y++)
                        @for ($x = 0; $x < $size; $x++)
                            @if(!empty($qrMatrix[$y][$x]))
                                <rect x="{{ $x + $offset }}" y="{{ $y + $offset }}" width="1" height="1" fill="#000"/>
                            @endif
                        @endfor
                    @endfor
                </svg>
            </td>
            <td class="qr-text">
                <div class="qr-payload">{{ $qrPayload }}</div>
                <div class="small-muted">
                    Representación impresa del comprobante electrónico emitido por el sistema.
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
