@extends('layouts.app')

@section('title', 'Riwayat Pemesanan')

@section('content')
    <section class="page-hero py-4 py-lg-5">
        <div class="container">
            <p class="section-eyebrow mb-2">Akun pelanggan</p>
            <h1 class="page-title mb-2">Riwayat pemesanan</h1>
            <p class="text-muted-custom mb-0">Pantau status terbaru dan detail setiap pesanan Anda.</p>
        </div>
    </section>

    <section class="section-space pt-4">
        <div class="container order-history-list">
            @if(session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            @if($orders->isEmpty())
                <div class="order-empty-state">
                    <i class="bi bi-receipt" aria-hidden="true"></i>
                    <h2 class="h4">Belum ada pesanan</h2>
                    <p class="text-muted-custom">Pilih menu favorit untuk membuat pre-order pertama Anda.</p>
                    <a class="btn btn-primary" href="{{ route('menu.index') }}">Jelajahi menu</a>
                </div>
            @else
                @foreach($orders as $order)
                    @php
                        $status = \App\OrderStatus::tryFrom($order->status);
                    @endphp
                    <article class="order-history-card">
                        <div class="order-history-heading">
                            <div>
                                <span class="order-history-code">{{ $order->transaction_code }}</span>
                                <p class="small text-muted-custom mb-0">Dipesan {{ $order->created_at->format('d M Y, H:i') }}</p>
                            </div>
                            <span class="order-status-badge {{ $status?->badgeClass() ?? 'order-status-unknown' }}">
                                <i class="bi {{ $status === \App\OrderStatus::Confirmed ? 'bi-check-circle-fill' : ($status === \App\OrderStatus::Rejected ? 'bi-x-circle-fill' : 'bi-hourglass-split') }}" aria-hidden="true"></i>
                                {{ $status?->label() ?? ucfirst($order->status) }}
                            </span>
                        </div>

                        <div class="order-history-body">
                            <div>
                                <h2 class="h5 mb-3">Detail pesanan</h2>
                                <ul class="order-item-list">
                                    @foreach($order->items as $item)
                                        <li>
                                            <span>{{ $item->product_name }} <span class="text-muted-custom">× {{ $item->quantity }}</span></span>
                                            <strong>Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="small text-muted-custom mb-1">
                                    Pengambilan: {{ $order->pickup_date->translatedFormat('d F Y') }} · {{ substr((string) $order->pickup_time, 0, 5) }}
                                </p>
                                <p class="fw-semibold mb-0">Total Rp{{ number_format((float) $order->total_price, 0, ',', '.') }}</p>
                            </div>

                            <div class="order-notification-panel">
                                @if($status)
                                    <h3 class="h6 mb-2"><i class="bi bi-bell me-2" aria-hidden="true"></i>{{ $status->notificationTitle() }}</h3>
                                    <p class="small mb-0">{{ $status->notificationMessage() }}</p>
                                @else
                                    <h3 class="h6 mb-2">Informasi status</h3>
                                    <p class="small mb-0">Status pesanan: {{ $order->status }}.</p>
                                @endif
                                @if($status === \App\OrderStatus::Rejected && $order->statusHistories->first()?->reason)
                                    <p class="small mt-2 mb-0"><strong>Alasan:</strong> {{ $order->statusHistories->first()->reason }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="order-history-timeline">
                            <h3 class="h6">Riwayat perubahan status</h3>
                            @forelse($order->statusHistories as $history)
                                @php
                                    $historyStatus = \App\OrderStatus::tryFrom($history->new_status);
                                @endphp
                                <div class="order-history-event">
                                    <span class="order-history-dot {{ $historyStatus?->badgeClass() ?? 'order-status-unknown' }}" aria-hidden="true"></span>
                                    <div>
                                        <p class="mb-1">
                                            <strong>{{ $historyStatus?->label() ?? ucfirst($history->new_status) }}</strong>
                                            <span class="text-muted-custom">· {{ $history->created_at->format('d M Y, H:i') }}</span>
                                        </p>
                                        @if($historyStatus)
                                            <p class="small text-muted-custom mb-1">{{ $historyStatus->notificationMessage() }}</p>
                                        @endif
                                        @if($history->reason)
                                            <p class="small mb-0"><strong>Alasan:</strong> {{ $history->reason }}</p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="small text-muted-custom mb-0">Belum ada catatan perubahan status.</p>
                            @endforelse
                        </div>
                    </article>
                @endforeach

                {{ $orders->links() }}
            @endif
        </div>
    </section>
@endsection
