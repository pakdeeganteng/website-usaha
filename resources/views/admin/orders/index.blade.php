@extends('layouts.admin')

@section('title', 'Daftar Pesanan')

@section('content')
    @include('partials.admin-navigation')

    <main class="admin-dashboard">
        <div class="container-fluid admin-container">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Kelola pemesanan</span>
                    <h1>Daftar pesanan</h1>
                    <p>Periksa informasi pelanggan dan tindak lanjuti pesanan yang menunggu konfirmasi.</p>
                </div>
            </div>

            @if(session('status'))
                <div class="admin-alert admin-alert-success" role="status">{{ session('status') }}</div>
            @endif

            <section class="admin-dashboard-card order-admin-card" aria-labelledby="orders-title">
                <div class="order-admin-toolbar">
                    <h2 id="orders-title" class="h5 mb-0">Seluruh pesanan</h2>
                    <form method="GET" action="{{ route('admin.orders.index') }}" class="order-filter-form">
                        <label class="visually-hidden" for="order-status-filter">Filter status</label>
                        <select id="order-status-filter" name="status" class="form-select">
                            <option value="">Semua status</option>
                            @foreach(\App\OrderStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline-dark">Terapkan</button>
                    </form>
                </div>

                @if($orders->isEmpty())
                    <div class="order-empty-state">
                        <i class="bi bi-inbox" aria-hidden="true"></i>
                        <h3 class="h6">Belum ada pesanan untuk ditampilkan</h3>
                        <p class="text-muted mb-0">Pesanan baru akan muncul di halaman ini.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle order-admin-table">
                            <thead>
                                <tr>
                                    <th scope="col">Kode / tanggal</th>
                                    <th scope="col">Pemesan</th>
                                    <th scope="col">Item</th>
                                    <th scope="col">Pengambilan</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    @php
                                        $status = \App\OrderStatus::tryFrom($order->status);
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $order->transaction_code }}</strong>
                                            <span class="d-block small text-muted">{{ $order->created_at->format('d M Y, H:i') }}</span>
                                        </td>
                                        <td>
                                            {{ $order->customer_name }}
                                            <span class="d-block small text-muted">{{ $order->customer_whatsapp }}</span>
                                        </td>
                                        <td>{{ $order->items_count }} item</td>
                                        <td>{{ $order->pickup_date->format('d M Y') }}<span class="d-block small text-muted">{{ substr((string) $order->pickup_time, 0, 5) }}</span></td>
                                        <td>Rp{{ number_format((float) $order->total_price, 0, ',', '.') }}</td>
                                        <td>
                                            <span class="order-status-badge {{ $status?->badgeClass() ?? 'order-status-unknown' }}">
                                                {{ $status?->label() ?? ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td><a class="btn btn-sm btn-outline-dark" href="{{ route('admin.orders.show', $order) }}">Lihat detail</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $orders->links() }}
                @endif
            </section>
        </div>
    </main>
@endsection
