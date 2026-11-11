<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan DapurHarian</title>
    <style>
        body { color: #202b39; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin-bottom: 4px; font-size: 20px; }
        h2 { margin: 18px 0 8px; font-size: 13px; }
        p { color: #667085; }
        .summary { width: 100%; margin-top: 12px; border-collapse: collapse; }
        .summary td { width: 25%; padding: 8px; border: 1px solid #dfe5ec; vertical-align: top; }
        .summary strong, .summary span { display: block; }
        .summary span { margin-top: 4px; font-size: 12px; }
        table.details { width: 100%; border-collapse: collapse; }
        .details th, .details td { padding: 6px; border: 1px solid #dfe5ec; text-align: left; }
        .details th { background: #edf2f7; }
        .note { margin-top: 10px; padding: 8px; background: #fff7e7; }
    </style>
</head>
<body>
    <h1>Laporan Penjualan &amp; Keuangan</h1>
    <p>Periode {{ $report['start']->format('d/m/Y') }} sampai {{ $report['end']->format('d/m/Y') }}</p>
    @php
        $summary = $report['summary'];
        $currency = fn ($amount) => $amount === null ? 'Belum dapat dihitung' : 'Rp '.number_format((float) $amount, 0, ',', '.');
    @endphp
    @unless ($summary['cost_available'])
        <p class="note">HPP dan laba belum dapat dihitung akurat karena terdapat transaksi atau retur tanpa data modal historis.</p>
    @endunless
    <table class="summary">
        <tr>
            <td><strong>Omzet bersih</strong><span>{{ $currency($summary['revenue']) }}</span></td>
            <td><strong>HPP</strong><span>{{ $currency($summary['cost_of_goods']) }}</span></td>
            <td><strong>Pengeluaran</strong><span>{{ $currency($summary['expenses']) }}</span></td>
            <td><strong>Laba bersih</strong><span>{{ $currency($summary['net_profit']) }}</span></td>
        </tr>
        <tr>
            <td><strong>Transaksi dibayar</strong><span>{{ number_format($summary['transaction_count'], 0, ',', '.') }}</span></td>
            <td><strong>Produk terjual / retur</strong><span>{{ number_format($summary['products_sold'], 0, ',', '.') }} / {{ number_format($summary['products_returned'], 0, ',', '.') }}</span></td>
            <td><strong>Refund</strong><span>{{ $currency($summary['refund_total']) }}</span></td>
            <td><strong>Kerugian</strong><span>{{ $currency($summary['loss']) }}</span></td>
        </tr>
    </table>
    <h2>Detail transaksi</h2>
    <table class="details">
        <thead><tr><th>Transaksi</th><th>Tanggal</th><th>Produk</th><th>Jumlah</th><th>Harga jual</th><th>Modal</th><th>Total penjualan</th><th>Laba item</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($orders as $order)
                @foreach ($order->items as $item)
                    @php
                        $profit = $item->unit_cost === null
                            ? null
                            : ((float) $item->unit_price - (float) $item->unit_cost) * $item->quantity;
                    @endphp
                    <tr>
                        <td>{{ $order->transaction_code }}</td>
                        <td>{{ $order->paid_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $currency($item->unit_price) }}</td>
                        <td>{{ $currency($item->unit_cost) }}</td>
                        <td>{{ $currency($item->subtotal) }}</td>
                        <td>{{ $currency($profit) }}</td>
                        <td>{{ $order->payment_status }} / {{ $order->status }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    <h2>Refund dan retur</h2>
    <table class="details">
        <thead><tr><th>Tanggal</th><th>Transaksi</th><th>Produk</th><th>Jumlah retur</th><th>Jumlah refund</th><th>Catatan</th></tr></thead>
        <tbody>
            @forelse ($refunds as $refund)
                <tr>
                    <td>{{ $refund->refunded_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $refund->order->transaction_code }}</td>
                    <td>{{ $refund->orderItem?->product_name ?? 'Pengembalian dana' }}</td>
                    <td>{{ $refund->quantity_returned }}</td>
                    <td>{{ $currency($refund->amount) }}</td>
                    <td>{{ $refund->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Tidak ada refund pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    <h2>Pengeluaran operasional</h2>
    <table class="details">
        <thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th>Jumlah</th></tr></thead>
        <tbody>
            @forelse ($expenses as $expense)
                <tr>
                    <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                    <td>{{ $expense->category }}</td>
                    <td>{{ $expense->description }}</td>
                    <td>{{ $currency($expense->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Tidak ada pengeluaran pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
