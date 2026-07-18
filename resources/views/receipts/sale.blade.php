<!doctype html>
<html>
<head>
    <title>Sale #{{ $sale->id }}</title>
    <meta charset="utf-8" />
    <style>
        :root { color-scheme: light; }
        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            color: #0f172a;
            background: #fff;
        }
        .receipt { max-width: 360px; margin: 0 auto; }
        .header {
            text-align: center;
            padding-bottom: 16px;
            border-bottom: 1px dashed #cbd5e1;
            margin-bottom: 16px;
        }
        .brand { font-size: 12px; letter-spacing: 0.18em; font-weight: 700; color: #0f766e; }
        h1 { margin: 6px 0 4px; font-size: 22px; }
        .meta, .footer { font-size: 12px; color: #475569; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th {
            text-align: left;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        td { padding: 10px 0; vertical-align: top; border-bottom: 1px solid #f1f5f9; }
        .right { text-align: right; white-space: nowrap; }
        .item-name { font-weight: 700; }
        .item-sku { font-size: 11px; color: #64748b; }
        .summary { margin-top: 16px; padding-top: 12px; border-top: 1px dashed #cbd5e1; }
        .summary-row { display: flex; justify-content: space-between; gap: 16px; font-size: 13px; margin: 6px 0; }
        .summary-row.total { font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 10px; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="brand">{{ strtoupper($settings->pharmacy_name) }}</div>
            <h1>POS Bill</h1>
            @if ($settings->owner_name)
                <div class="meta">Owner: {{ $settings->owner_name }}</div>
            @endif
            @if ($settings->phone)
                <div class="meta">Phone: {{ $settings->phone }}</div>
            @endif
            @if ($settings->address)
                <div class="meta">{{ $settings->address }}</div>
            @endif
            <div class="meta">Sale #{{ $sale->id }} · Bill Code: {{ $sale->bill_code }}</div>
            <div class="meta">{{ $sale->created_at->timezone('Asia/Karachi')->format('j F Y, g:i A') }} PKT</div>
        </div>

        <div class="meta">
            Customer: {{ $sale->customer_name ?? 'Walk-in' }}
            <br />Payment: {{ ucfirst($sale->payment_method) }}
            @if ($sale->notes)
                <br />Notes: {{ $sale->notes }}
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="right">Unit</th>
                    <th class="right">Qty</th>
                    <th class="right">Price</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->medicine->name ?? 'Unknown item' }}</div>
                            <div class="item-sku">{{ $item->medicine->medicine_type ?? '' }}</div>
                        </td>
                        <td class="right">{{ $item->unit_type }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="right">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row total">
                <span>Grand Total</span>
                <span>{{ number_format($sale->total_amount, 2) }}</span>
            </div>
        </div>

        <div class="footer" style="margin-top: 18px; text-align: center;">
            Thank you for shopping with {{ $settings->pharmacy_name }}.
        </div>
    </div>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
