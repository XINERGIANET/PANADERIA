<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        .sheet {
            width: 100%;
            border-collapse: collapse;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .subtitle {
            font-size: 12px;
            color: #475569;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            margin-bottom: 16px;
        }

        .meta td {
            padding: 4px 6px;
            border: 1px solid #e5e7eb;
        }

        .meta .label {
            background: #f8fafc;
            font-weight: 700;
            width: 160px;
        }

        .summary {
            margin: 12px 0 16px;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .summary strong {
            color: #111827;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #1f2937;
            color: #ffffff;
            text-align: left;
            padding: 8px 6px;
            border: 1px solid #111827;
            font-size: 10px;
        }

        .table td {
            padding: 7px 6px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .status {
            font-weight: 700;
            color: #ffffff;
            padding: 4px 8px;
            display: inline-block;
            border-radius: 4px;
            font-size: 10px;
        }

        .status-success { background: #16a34a; }
        .status-danger { background: #dc2626; }
        .status-warning { background: #d97706; }
        .status-secondary { background: #64748b; }

        .footer {
            margin-top: 16px;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <table class="sheet">
        <tr>
            <td>
                <div class="title">Historial de ventas</div>
                <div class="subtitle">Exportado el {{ now()->format('d/m/Y H:i:s') }}</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="label">Fecha inicial</td>
            <td>{{ $filters['start_date'] }}</td>
            <td class="label">Fecha final</td>
            <td>{{ $filters['end_date'] }}</td>
        </tr>
        <tr>
            <td class="label">N° comprobante</td>
            <td>{{ $filters['number'] }}</td>
            <td class="label">Tipo</td>
            <td>{{ $filters['voucher_type'] }}</td>
        </tr>
        <tr>
            <td class="label">Metodo de pago</td>
            <td>{{ $filters['payment_method'] }}</td>
            <td class="label">Turno</td>
            <td>{{ $filters['shift'] }}</td>
        </tr>
        <tr>
            <td class="label">Sede</td>
            <td>{{ $filters['location'] }}</td>
            <td class="label">Total de ventas</td>
            <td>S/ {{ number_format($total, 2, '.', ',') }}</td>
        </tr>
        <tr>
            <td class="label">Total pagado</td>
            <td>S/ {{ number_format($total_pagos, 2, '.', ',') }}</td>
            <td class="label">Registros</td>
            <td>{{ $sales->count() }}</td>
        </tr>
    </table>

    <div class="summary">
        <strong>Resumen:</strong>
        Se exportan {{ $sales->count() }} comprobantes con el filtro actual.
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 95px;">Comprobante</th>
                <th style="width: 70px;">Tipo</th>
                <th style="width: 180px;">Cliente</th>
                <th style="width: 90px;">Documento</th>
                <th style="width: 90px;">Fecha</th>
                <th style="width: 90px;" class="text-right">Total</th>
                <th style="width: 90px;" class="text-right">Saldo</th>
                <th style="width: 110px;">Estado SUNAT</th>
                <th style="width: 70px;">Sede</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
                @php
                    $saleStatus = $sunatStatuses[$sale->id]['status'] ?? $sale->voucher_status ?? 'PENDIENTE';
                    $statusClass = $sunatStatuses[$sale->id]['class'] ?? 'secondary';
                    $clientName = trim((string) ($sale->client_name ?? optional($sale->client)->business_name ?? optional($sale->client)->contact_name ?? 'Varios'));
                    $clientDoc = trim((string) (optional($sale->client)->document ?? ''));
                    $locationName = optional($sale->location)->name ?? 'N/A';
                @endphp
                <tr>
                    <td>{{ $sale->number ?: 'N/A' }}</td>
                    <td>{{ $sale->voucher_type }}</td>
                    <td>{{ $clientName }}</td>
                    <td>{{ $clientDoc !== '' ? $clientDoc : '---' }}</td>
                    <td>{{ optional($sale->date)->format('d/m/Y H:i') }}</td>
                    <td class="text-right">S/ {{ number_format((float) $sale->total, 2, '.', ',') }}</td>
                    <td class="text-right">S/ {{ number_format((float) $sale->saldo(), 2, '.', ',') }}</td>
                    <td>
                        <span class="status status-{{ $statusClass }}">{{ $saleStatus }}</span>
                    </td>
                    <td>{{ $locationName }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Archivo generado desde el sistema. Formato compatible con Excel.
    </div>
</body>
</html>
