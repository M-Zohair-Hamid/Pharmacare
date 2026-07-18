@php
    $paperWidth = $settings->receipt_paper_width === '58' ? '58' : '80';
    // Printable width is slightly less than the physical roll width on real
    // thermal printers (roughly 48mm on 58mm rolls, 72mm on 80mm rolls).
    $printableWidthMm = $paperWidth === '58' ? 48 : 72;
    $baseFontPx = $paperWidth === '58' ? 10 : 11;
@endphp
<!doctype html>
<html>
<head>
    <title>Sale #{{ $sale->id }}</title>
    <meta charset="utf-8" />
    <style>
        :root { color-scheme: light; }

        /* Zero-margin page sized to the physical roll so the browser print
           dialog doesn't add its own default margins around the receipt. */
        @@page {
            size: {{ $paperWidth }}mm auto;
            margin: 0;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Courier New', Consolas, monospace;
            color: #000;
            background: #fff;
            font-size: {{ $baseFontPx }}px;
        }

        .receipt {
            width: {{ $printableWidthMm }}mm;
            margin: 0 auto;
            padding: 2mm 1.5mm;
        }

        .header {
            text-align: center;
            padding-bottom: 2mm;
            border-bottom: 1px dashed #000;
            margin-bottom: 2mm;
        }
        .brand { font-size: 1.1em; font-weight: 700; letter-spacing: 0.05em; }
        h1 { margin: 1mm 0; font-size: 1.05em; font-weight: 700; }
        .meta, .footer { font-size: 0.92em; line-height: 1.5; }

        .divider { border-top: 1px dashed #000; margin: 2mm 0; }

        .items { margin-top: 1mm; }
        .item-row { padding: 1.5mm 0; border-bottom: 1px dotted #999; }
        .item-row:last-child { border-bottom: none; }
        .item-name { font-weight: 700; font-size: 0.95em; }
        .item-line {
            display: flex;
            justify-content: space-between;
            font-size: 0.88em;
            margin-top: 0.5mm;
        }

        .summary { margin-top: 2mm; padding-top: 2mm; border-top: 1px dashed #000; }
        .summary-row { display: flex; justify-content: space-between; gap: 8px; font-size: 0.95em; margin: 1mm 0; }
        .summary-row.total { font-size: 1.15em; font-weight: 700; margin-top: 1.5mm; }

        .refund-policy, .refunded-banner {
            margin-top: 2mm;
            padding-top: 2mm;
            border-top: 1px dashed #000;
            font-size: 0.85em;
            text-align: center;
        }
        .refunded-banner { font-weight: 700; }

        .footer { margin-top: 3mm; text-align: center; }

        /* Screen-only preview chrome; hidden entirely when actually printing. */
        @media screen {
            body { background: #e2e8f0; padding: 16px 0; }
            .receipt {
                background: #fff;
                box-shadow: 0 1px 3px rgba(0,0,0,0.15);
                min-height: 100px;
            }
        }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt { box-shadow: none; }
        }
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
            <div class="meta">Bill: {{ $sale->bill_code }}</div>
            <div class="meta">{{ $sale->created_at->timezone('Asia/Karachi')->format('j M Y, g:i A') }} PKT</div>
        </div>

        @if ($sale->is_refunded)
            <div class="refunded-banner">*** REFUNDED ***</div>
            <div class="divider"></div>
        @endif

        <div class="meta">
            Customer: {{ $sale->customer_name ?? 'Walk-in' }}<br />
            Payment: {{ ucfirst($sale->payment_method) }}
            @if ($sale->notes)
                <br />Notes: {{ $sale->notes }}
            @endif
        </div>

        <div class="divider"></div>

        <div class="items">
            @foreach ($sale->items as $item)
                <div class="item-row">
                    <div class="item-name">{{ $item->medicine->name ?? 'Unknown item' }}</div>
                    <div class="item-line">
                        <span>{{ $item->quantity }} {{ $item->unit_type }} &times; {{ number_format($item->unit_price, 2) }}</span>
                        <span>{{ number_format($item->subtotal, 2) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="summary">
            <div class="summary-row total">
                <span>TOTAL</span>
                <span>{{ number_format($sale->total_amount, 2) }}</span>
            </div>
        </div>

        @if ($settings->refunds_enabled && $settings->refund_window_days && !$sale->is_refunded)
            <div class="refund-policy">
                Returns accepted within {{ $settings->refund_window_days }} {{ $settings->refund_window_days == 1 ? 'day' : 'days' }} of purchase with this receipt.
            </div>
        @endif

        @if ($sale->is_refunded)
            <div class="refund-policy">
                Refunded {{ $sale->refunded_at->timezone('Asia/Karachi')->format('j M Y, g:i A') }} PKT
                @if ($sale->refund_reason)
                    <br />Reason: {{ $sale->refund_reason }}
                @endif
            </div>
        @endif

        <div class="footer">
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
