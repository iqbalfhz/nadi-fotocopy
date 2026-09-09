@php
    use App\Support\Rupiah;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $transaction->transaction_number }}</title>
    <style>
        /* Layout struk thermal 58mm/80mm — dicetak lewat dialog print browser (§4.2). */
        :root { --struk-width: 72mm; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 16px;
            background: #f4f4f5;
            font-family: "Courier New", ui-monospace, monospace;
            color: #000;
        }

        .sheet {
            width: var(--struk-width);
            margin: 0 auto;
            background: #fff;
            padding: 8px 10px;
            font-size: 11px;
            line-height: 1.45;
        }

        .actions {
            width: var(--struk-width);
            margin: 0 auto 12px;
            display: flex;
            gap: 8px;
        }

        .actions a, .actions button {
            flex: 1;
            padding: 8px 10px;
            font: inherit;
            font-size: 12px;
            text-align: center;
            text-decoration: none;
            border: 1px solid #d4d4d8;
            border-radius: 6px;
            background: #fff;
            color: #18181b;
            cursor: pointer;
        }

        .actions .primary { background: #18181b; color: #fff; border-color: #18181b; }

        .center { text-align: center; }
        .bold { font-weight: 700; }
        .sep { border-top: 1px dashed #000; margin: 6px 0; }

        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .num { text-align: right; white-space: nowrap; }
        .void { border: 1px solid #000; padding: 4px; margin-top: 6px; text-align: center; font-weight: 700; }

        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .sheet { width: auto; padding: 0; }
            @page { margin: 4mm; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="primary" onclick="window.print()">Cetak</button>
        @if ($waLink)
            <a href="{{ $waLink }}" target="_blank" rel="noopener">Kirim WA</a>
        @endif
        <a href="{{ route('pos.kasir') }}">Kasir</a>
    </div>

    <div class="sheet">
        <div class="center">
            <div class="bold">{{ $storeName }}</div>
            @if ($storeAddress)<div>{{ $storeAddress }}</div>@endif
            @if ($storeWhatsapp)<div>WA: {{ $storeWhatsapp }}</div>@endif
        </div>

        <div class="sep"></div>

        <table>
            <tr><td>No</td><td class="num">{{ $transaction->transaction_number }}</td></tr>
            <tr><td>Tanggal</td><td class="num">{{ $transaction->created_at->format('d/m/Y H:i') }}</td></tr>
            <tr><td>Kasir</td><td class="num">{{ $transaction->user->name }}</td></tr>
        </table>

        <div class="sep"></div>

        <table>
            @foreach ($transaction->items as $item)
                <tr><td colspan="2">{{ $item->product_name }}</td></tr>
                <tr>
                    <td>{{ $item->qty }} x {{ Rupiah::plain($item->price) }}</td>
                    <td class="num">{{ Rupiah::plain($item->subtotal) }}</td>
                </tr>
            @endforeach
        </table>

        <div class="sep"></div>

        <table>
            <tr><td>Subtotal</td><td class="num">{{ Rupiah::plain($transaction->subtotal) }}</td></tr>
            @if ($transaction->discount_amount > 0)
                <tr><td>Diskon</td><td class="num">-{{ Rupiah::plain($transaction->discount_amount) }}</td></tr>
            @endif
            <tr class="bold"><td>TOTAL</td><td class="num">{{ Rupiah::plain($transaction->total) }}</td></tr>
            <tr><td>{{ $transaction->payment_method->label() }}</td><td class="num">{{ Rupiah::plain($transaction->paid_amount) }}</td></tr>
            @if ($transaction->change_amount > 0)
                <tr><td>Kembali</td><td class="num">{{ Rupiah::plain($transaction->change_amount) }}</td></tr>
            @endif
        </table>

        @if ($transaction->isVoided())
            <div class="void">** TRANSAKSI DIBATALKAN **</div>
        @endif

        <div class="sep"></div>

        <div class="center">{{ $receiptFooter }}</div>
    </div>
</body>
</html>
