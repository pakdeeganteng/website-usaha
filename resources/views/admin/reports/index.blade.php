@extends('layouts.admin')

@section('title', 'Laporan Keuangan')

@section('content')
    @include('partials.admin-navigation')

    @php
        $summary = $report['summary'];
        $currency = fn ($amount) => $amount === null ? 'Belum dapat dihitung' : 'Rp '.number_format((float) $amount, 0, ',', '.');
        $trendMaximum = max(1, collect($report['trend'])->max(fn ($point) => max(
            (float) $point['revenue'],
            (float) $point['expenses'],
            abs((float) ($point['profit'] ?? 0)),
            (float) $point['loss'],
        )));
        $periodLabel = match ($period) {
            'monthly' => $reference->translatedFormat('F Y'),
            'yearly' => $reference->format('Y'),
            default => $reference->translatedFormat('d F Y'),
        };
    @endphp

    <main class="admin-dashboard financial-dashboard">
        <div class="container-fluid admin-container">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Analisis usaha</span>
                    <h1>Laporan penjualan &amp; keuangan</h1>
                    <p>Data pembayaran, modal produk, pengeluaran, serta pengembalian yang tercatat.</p>
                </div>
                <div class="report-export-actions">
                    <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.reports.export.pdf', request()->query()) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                    </a>
                    <a class="btn btn-outline-dark btn-sm" href="{{ route('admin.reports.export.excel', request()->query()) }}">
                        <i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i> Excel
                    </a>
                    <button class="btn btn-outline-dark btn-sm" type="button" onclick="window.print()">
                        <i class="bi bi-printer me-1" aria-hidden="true"></i> Cetak
                    </button>
                </div>
            </div>

            @if (session('status'))
                <div class="alert admin-alert-success" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Perubahan belum disimpan.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="admin-dashboard-card report-filter-card mb-4" aria-label="Filter laporan">
                <form method="GET" action="{{ route('admin.reports.index') }}" class="report-filter-form">
                    <div>
                        <label class="form-label" for="period">Periode laporan</label>
                        <select class="form-select" id="period" name="period">
                            <option value="daily" @selected($period === 'daily')>Harian</option>
                            <option value="monthly" @selected($period === 'monthly')>Bulanan</option>
                            <option value="yearly" @selected($period === 'yearly')>Tahunan</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="date">Tanggal</label>
                        <input class="form-control" id="date" name="date" type="date" value="{{ $reference->format('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="form-label" for="month">Bulan</label>
                        <input class="form-control" id="month" name="month" type="month" value="{{ $reference->format('Y-m') }}">
                    </div>
                    <div>
                        <label class="form-label" for="year">Tahun</label>
                        <input class="form-control" id="year" name="year" type="number" min="2000" max="2100" value="{{ $reference->format('Y') }}">
                    </div>
                    <div class="report-search-field">
                        <label class="form-label" for="search">Cari transaksi atau produk</label>
                        <input class="form-control" id="search" name="search" type="search" maxlength="100" value="{{ $search }}" placeholder="Kode transaksi, pelanggan, produk">
                    </div>
                    <button class="btn btn-primary align-self-end" type="submit">
                        <i class="bi bi-funnel me-1" aria-hidden="true"></i> Terapkan
                    </button>
                </form>
                <p class="report-period-caption mb-0">Periode data: <strong>{{ $report['start']->format('d/m/Y') }}</strong> – <strong>{{ $report['end']->format('d/m/Y') }}</strong></p>
            </section>

            @unless ($summary['cost_available'])
                <div class="alert alert-warning report-warning" role="status">
                    <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
                    HPP dan laba belum dapat dihitung secara akurat karena terdapat transaksi atau retur dengan data modal yang belum tersedia.
                    Modal produk yang baru dicatat tidak mengubah snapshot transaksi lama.
                </div>
            @endunless

            <section class="report-stat-grid" aria-label="Ringkasan keuangan {{ $periodLabel }}">
                <article class="report-stat-card report-stat-revenue">
                    <span>Omzet bersih</span>
                    <strong>{{ $currency($summary['revenue']) }}</strong>
                    <small>Penjualan {{ $currency($summary['gross_revenue']) }} − refund {{ $currency($summary['refund_total']) }}</small>
                </article>
                <article class="report-stat-card">
                    <span>HPP produk</span>
                    <strong>{{ $currency($summary['cost_of_goods']) }}</strong>
                    <small>Termasuk pembalikan modal dari barang retur</small>
                </article>
                <article class="report-stat-card">
                    <span>Pengeluaran operasional</span>
                    <strong>{{ $currency($summary['expenses']) }}</strong>
                    <small>Biaya tercatat pada periode ini</small>
                </article>
                <article class="report-stat-card">
                    <span>Laba kotor</span>
                    <strong>{{ $currency($summary['gross_profit']) }}</strong>
                    <small>Omzet bersih dikurangi HPP bersih</small>
                </article>
                <article class="report-stat-card {{ $summary['loss'] > 0 ? 'report-stat-negative' : '' }}">
                    <span>Total kerugian</span>
                    <strong>{{ $currency($summary['loss']) }}</strong>
                    <small>Selisih rugi setelah pengeluaran</small>
                </article>
                <article class="report-stat-card {{ ($summary['net_profit'] ?? 0) >= 0 ? 'report-stat-positive' : 'report-stat-negative' }}">
                    <span>Laba bersih</span>
                    <strong>{{ $currency($summary['net_profit']) }}</strong>
                    <small>Laba kotor dikurangi pengeluaran</small>
                </article>
                <article class="report-stat-card">
                    <span>Transaksi dibayar</span>
                    <strong>{{ number_format($summary['transaction_count'], 0, ',', '.') }}</strong>
                    <small>Pesanan batal dan belum dibayar dikecualikan</small>
                </article>
                <article class="report-stat-card">
                    <span>Produk terjual</span>
                    <strong>{{ number_format($summary['products_sold'], 0, ',', '.') }}</strong>
                    <small>{{ number_format($summary['products_returned'], 0, ',', '.') }} unit diretur pada periode</small>
                </article>
            </section>

            @if (in_array($period, ['monthly', 'yearly'], true) && $report['previous'] !== null)
                @php
                    $currentRevenue = (float) $summary['revenue'];
                    $previousRevenue = (float) $report['previous']['revenue'];
                    $revenueChange = $previousRevenue > 0
                        ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
                        : null;
                @endphp
                <p class="report-comparison-note">
                    Perbandingan omzet bersih dengan {{ $period === 'monthly' ? 'bulan' : 'tahun' }} sebelumnya:
                    @if ($revenueChange === null)
                        <strong>{{ $previousRevenue === 0.0 ? 'Tidak ada dasar perbandingan' : '—' }}</strong>
                    @else
                        <strong class="{{ $revenueChange >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $revenueChange > 0 ? '+' : '' }}{{ number_format($revenueChange, 1, ',', '.') }}%
                        </strong>
                    @endif
                    ({{ $currency($report['previous']['revenue']) }}).
                </p>
            @endif

            <section class="admin-dashboard-card report-chart-card mb-4" aria-labelledby="report-chart-title">
                <div class="admin-card-heading">
                    <span class="admin-card-icon"><i class="bi bi-bar-chart-line" aria-hidden="true"></i></span>
                    <div>
                        <h2 id="report-chart-title">Perbandingan omzet, biaya &amp; hasil bersih</h2>
                        <p>Grafik berdasarkan data aktual. Laba ditandai merah bila nilainya negatif.</p>
                    </div>
                </div>
                <div class="report-chart-legend" aria-label="Keterangan grafik">
                    <span><i class="legend-dot legend-revenue"></i> Omzet</span>
                    <span><i class="legend-dot legend-expenses"></i> Pengeluaran</span>
                    <span><i class="legend-dot legend-profit"></i> Laba</span>
                    <span><i class="legend-dot legend-loss"></i> Rugi</span>
                </div>
                <div class="report-chart" role="img" aria-label="Grafik omzet, pengeluaran, laba dan rugi {{ $periodLabel }}">
                    @foreach ($report['trend'] as $point)
                        @php
                            $profit = $point['profit'];
                            $bars = [
                                ['key' => 'revenue', 'value' => (float) $point['revenue'], 'class' => 'bar-revenue', 'label' => 'Omzet'],
                                ['key' => 'expenses', 'value' => (float) $point['expenses'], 'class' => 'bar-expenses', 'label' => 'Pengeluaran'],
                                ['key' => 'profit', 'value' => $profit === null ? 0.0 : abs((float) $profit), 'class' => $profit === null ? 'bar-unavailable' : ($profit < 0 ? 'bar-loss' : 'bar-profit'), 'label' => $profit === null ? 'Laba belum dapat dihitung' : ($profit < 0 ? 'Rugi' : 'Laba')],
                            ];
                        @endphp
                        <div class="report-chart-group" title="{{ $point['label'] }}">
                            <div class="report-chart-bars">
                                @foreach ($bars as $bar)
                                    @php
                                        $height = $bar['value'] > 0
                                            ? max(2, ($bar['value'] / $trendMaximum) * 100)
                                            : 1;
                                    @endphp
                                    <span class="report-chart-bar {{ $bar['class'] }}" style="height: {{ number_format($height, 2, '.', '') }}%" aria-label="{{ $bar['label'] }}: {{ $bar['key'] === 'profit' ? $currency($profit) : $currency($bar['value']) }}"></span>
                                @endforeach
                            </div>
                            <small>{{ $point['label'] }}</small>
                        </div>
                    @endforeach
                </div>
            </section>

            @if ($period === 'yearly')
                <section class="admin-dashboard-card mb-4" aria-labelledby="best-sellers-title">
                    <div class="admin-card-heading">
                        <span class="admin-card-icon"><i class="bi bi-trophy" aria-hidden="true"></i></span>
                        <div>
                            <h2 id="best-sellers-title">Produk terlaris tahun {{ $reference->format('Y') }}</h2>
                            <p>Jumlah unit terjual dikurangi retur barang yang tercatat pada tahun ini.</p>
                        </div>
                    </div>
                    @if ($report['bestSellers']->isEmpty())
                        <p class="admin-empty-note mb-0">Belum ada data penjualan pada periode ini.</p>
                    @else
                        <ol class="report-best-sellers mb-0">
                            @foreach ($report['bestSellers'] as $product)
                                <li><span>{{ $product['name'] }}</span><strong>{{ number_format($product['quantity'], 0, ',', '.') }} unit</strong></li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            @endif

            <section class="admin-dashboard-card report-table-card mb-4" aria-labelledby="report-transactions-title">
                <div class="admin-card-heading">
                    <span class="admin-card-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                    <div>
                        <h2 id="report-transactions-title">Detail transaksi</h2>
                        <p>Baris item menggunakan harga dan snapshot modal saat transaksi dibuat.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table report-table align-middle">
                        <thead>
                            <tr>
                                <th>Nomor transaksi / tanggal</th>
                                <th>Produk</th>
                                <th>Jumlah</th>
                                <th>Harga jual</th>
                                <th>Modal</th>
                                <th>Total penjualan</th>
                                <th>Laba item</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                @foreach ($order->items as $item)
                                    @php
                                        $itemProfit = $item->unit_cost === null
                                            ? null
                                            : ((float) $item->unit_price - (float) $item->unit_cost) * $item->quantity;
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $order->transaction_code }}</strong><small>{{ $order->paid_at?->format('d/m/Y H:i') }}</small></td>
                                        <td>{{ $item->product_name }}</td>
                                        <td>{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                        <td>{{ $currency($item->unit_price) }}</td>
                                        <td>{{ $currency($item->unit_cost) }}</td>
                                        <td>{{ $currency($item->subtotal) }}</td>
                                        <td class="{{ ($itemProfit ?? 0) < 0 ? 'text-danger' : 'text-success' }}">{{ $currency($itemProfit) }}</td>
                                        <td><span class="report-status">{{ str_replace('_', ' ', ucfirst($order->payment_status)) }} · {{ ucfirst($order->status) }}</span></td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="8" class="report-empty-row">Tidak ada transaksi yang sesuai dengan periode dan pencarian.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if (method_exists($orders, 'links'))
                    <div class="report-pagination">{{ $orders->links() }}</div>
                @endif
            </section>

            <section class="report-management-grid mb-4" aria-label="Pencatatan data keuangan">
                <article class="admin-dashboard-card">
                    <div class="admin-card-heading">
                        <span class="admin-card-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
                        <div><h2>Catat pengeluaran</h2><p>Pengeluaran dicatat satu kali berdasarkan tanggal biaya.</p></div>
                    </div>
                    <form method="POST" action="{{ route('admin.reports.expenses.store') }}" class="report-form-grid">
                        @csrf
                        <div><label class="form-label" for="expense-category">Kategori</label><input class="form-control" id="expense-category" name="category" maxlength="120" required></div>
                        <div><label class="form-label" for="expense-date">Tanggal</label><input class="form-control" id="expense-date" name="expense_date" type="date" value="{{ now()->toDateString() }}" required></div>
                        <div class="report-form-wide"><label class="form-label" for="expense-description">Keterangan</label><input class="form-control" id="expense-description" name="description" maxlength="255" required></div>
                        <div><label class="form-label" for="expense-amount">Jumlah (Rp)</label><input class="form-control" id="expense-amount" name="amount" type="number" min="0.01" step="0.01" required></div>
                        <button class="btn btn-primary align-self-end" type="submit">Simpan pengeluaran</button>
                    </form>
                    <div class="report-mini-list">
                        <h3>Pengeluaran terbaru pada periode</h3>
                        @forelse ($expenses as $expense)
                            <div><span>{{ $expense->expense_date->format('d/m/Y') }} · {{ $expense->category }}<small>{{ $expense->description }}</small></span><strong>{{ $currency($expense->amount) }}</strong></div>
                        @empty
                            <p class="admin-empty-note mb-0">Belum ada pengeluaran yang dicatat.</p>
                        @endforelse
                    </div>
                </article>

                <article class="admin-dashboard-card">
                    <div class="admin-card-heading">
                        <span class="admin-card-icon"><i class="bi bi-arrow-return-left" aria-hidden="true"></i></span>
                        <div><h2>Catat refund / retur</h2><p>Refund hanya dapat dicatat maksimal sebesar nilai pembayaran tersisa.</p></div>
                    </div>
                    <form method="POST" action="{{ route('admin.reports.refunds.store') }}" class="report-form-grid">
                        @csrf
                        <div class="report-form-wide">
                            <label class="form-label" for="refund-order">Transaksi</label>
                            <select class="form-select" id="refund-order" name="order_id" required>
                                <option value="">Pilih transaksi lunas</option>
                                @foreach ($refundableOrders as $order)
                                    <option value="{{ $order->id }}">{{ $order->transaction_code }} · {{ $order->customer_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="report-form-wide">
                            <label class="form-label" for="refund-item">Produk yang diretur (opsional)</label>
                            <select class="form-select" id="refund-item" name="order_item_id">
                                <option value="">Tidak ada barang yang diretur</option>
                                @foreach ($refundableItems as $item)
                                    @php
                                        $availableQuantity = max(0, $item->quantity - (int) ($item->returned_quantity ?? 0));
                                    @endphp
                                    @if ($availableQuantity > 0)
                                        <option value="{{ $item->id }}">{{ $item->order->transaction_code }} · {{ $item->product_name }} (tersisa {{ $availableQuantity }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div><label class="form-label" for="refund-quantity">Jumlah unit diretur</label><input class="form-control" id="refund-quantity" name="quantity_returned" type="number" min="0" value="0" required></div>
                        <div><label class="form-label" for="refund-amount">Refund (Rp)</label><input class="form-control" id="refund-amount" name="amount" type="number" min="0.01" step="0.01" required></div>
                        <div class="report-form-wide"><label class="form-label" for="refund-notes">Catatan</label><input class="form-control" id="refund-notes" name="notes" maxlength="255"></div>
                        <button class="btn btn-primary align-self-end" type="submit">Simpan refund</button>
                    </form>
                    <div class="report-mini-list">
                        <h3>Refund terbaru pada periode</h3>
                        @forelse ($refunds as $refund)
                            <div><span>{{ $refund->refunded_at?->format('d/m/Y') }} · {{ $refund->order->transaction_code }}<small>{{ $refund->notes ?: 'Tanpa catatan' }}</small></span><strong>{{ $currency($refund->amount) }}</strong></div>
                        @empty
                            <p class="admin-empty-note mb-0">Belum ada refund yang dicatat.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="report-management-grid" aria-label="Kelola data produk dan pembayaran">
                <article class="admin-dashboard-card">
                    <div class="admin-card-heading">
                        <span class="admin-card-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                        <div><h2>Lengkapi modal produk</h2><p>Modal yang belum tersedia tidak akan diperkirakan dalam laporan laba.</p></div>
                    </div>
                    @forelse ($productsWithoutCost as $product)
                        <form method="POST" action="{{ route('admin.reports.products.cost', $product) }}" class="report-cost-row">
                            @csrf
                            @method('PUT')
                            <strong>{{ $product->name }}</strong>
                            <label class="visually-hidden" for="cost-{{ $product->id }}">Modal satuan {{ $product->name }}</label>
                            <input class="form-control" id="cost-{{ $product->id }}" name="cost_price" type="number" min="0" step="0.01" placeholder="Modal per unit">
                            <button class="btn btn-outline-dark btn-sm" type="submit">Simpan</button>
                        </form>
                    @empty
                        <p class="admin-empty-note mb-0">Semua produk sudah memiliki modal tercatat.</p>
                    @endforelse
                </article>

                <article class="admin-dashboard-card">
                    <div class="admin-card-heading">
                        <span class="admin-card-icon"><i class="bi bi-credit-card" aria-hidden="true"></i></span>
                        <div><h2>Konfirmasi pembayaran</h2><p>Penjualan baru masuk laporan setelah ditandai lunas.</p></div>
                    </div>
                    @forelse ($unpaidOrders as $order)
                        <div class="report-payment-row">
                            <span><strong>{{ $order->transaction_code }}</strong><small>{{ $order->customer_name }} · {{ $currency($order->total_price) }}</small></span>
                            <form method="POST" action="{{ route('admin.reports.orders.payment', $order) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-outline-dark btn-sm" type="submit">Tandai lunas</button>
                            </form>
                        </div>
                    @empty
                        <p class="admin-empty-note mb-0">Tidak ada pesanan belum dibayar yang perlu dikonfirmasi.</p>
                    @endforelse
                </article>
            </section>
        </div>
    </main>
@endsection
