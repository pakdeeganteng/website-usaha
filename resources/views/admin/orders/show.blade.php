@extends('layouts.admin')

@section('title', 'Detail Pesanan')

@section('content')
    @include('partials.admin-navigation')

    @php
        $status = \App\OrderStatus::tryFrom($order->status);
        $canUpdateStatus = $status === \App\OrderStatus::Pending;
    @endphp

    <main class="admin-dashboard">
        <div class="container-fluid admin-container">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Detail pemesanan</span>
                    <h1>{{ $order->transaction_code }}</h1>
                    <p>Dibuat {{ $order->created_at->format('d M Y, H:i') }}</p>
                </div>
                <a class="btn btn-outline-dark" href="{{ route('admin.orders.index') }}">Kembali ke daftar</a>
            </div>

            @if(session('status'))
                <div class="admin-alert admin-alert-success" role="status">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="admin-alert admin-alert-error" role="alert">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="order-admin-detail-grid">
                <section class="admin-dashboard-card order-admin-card">
                    <h2 class="h5 mb-3">Informasi pemesan</h2>
                    <dl class="order-detail-list">
                        <div><dt>Nama</dt><dd>{{ $order->customer_name }}</dd></div>
                        <div><dt>WhatsApp</dt><dd>{{ $order->customer_whatsapp }}</dd></div>
                        <div><dt>Tanggal pengambilan</dt><dd>{{ $order->pickup_date->format('d M Y') }}</dd></div>
                        <div><dt>Waktu pengambilan</dt><dd>{{ substr((string) $order->pickup_time, 0, 5) }}</dd></div>
                        <div><dt>Pembayaran</dt><dd>{{ $order->payment_status === 'unpaid' ? 'Belum dibayar' : ucfirst(str_replace('_', ' ', $order->payment_status)) }}</dd></div>
                        @if($order->note)
                            <div><dt>Catatan pelanggan</dt><dd>{{ $order->note }}</dd></div>
                        @endif
                    </dl>

                    <h2 class="h5 mt-4 mb-3">Item pesanan</h2>
                    <ul class="order-item-list">
                        @foreach($order->items as $item)
                            <li>
                                <span>{{ $item->product_name }} × {{ $item->quantity }}<span class="d-block small text-muted">Rp{{ number_format((float) $item->unit_price, 0, ',', '.') }} / item</span></span>
                                <strong>Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}</strong>
                            </li>
                        @endforeach
                    </ul>
                    <p class="fw-semibold text-end mb-0">Total Rp{{ number_format((float) $order->total_price, 0, ',', '.') }}</p>
                </section>

                <div class="d-flex flex-column gap-4">
                    <section class="admin-dashboard-card order-admin-card">
                        <h2 class="h5 mb-3">Status pemesanan</h2>
                        <p>
                            <span class="order-status-badge {{ $status?->badgeClass() ?? 'order-status-unknown' }}">
                                {{ $status?->label() ?? ucfirst($order->status) }}
                            </span>
                        </p>
                        @if($status)
                            <h3 class="h6">{{ $status->notificationTitle() }}</h3>
                            <p class="text-muted mb-0">{{ $status->notificationMessage() }}</p>
                        @endif
                    </section>

                    @if($canUpdateStatus)
                        <section class="admin-dashboard-card order-admin-card">
                            <h2 class="h5 mb-3">Tindak lanjuti pesanan</h2>
                            <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="admin-form">
                                @csrf
                                @method('PATCH')
                                <div class="admin-field">
                                    <label for="status">Status baru</label>
                                    <select id="status" name="status" class="form-select" required>
                                        @foreach(\App\OrderStatus::cases() as $option)
                                            <option value="{{ $option->value }}" @selected(old('status', $status?->value) === $option->value)>
                                                {{ $option->label() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="admin-field">
                                    <label for="reason">Alasan penolakan <span class="text-muted">(wajib jika ditolak)</span></label>
                                    <textarea id="reason" name="reason" class="form-control" rows="3" maxlength="1000">{{ old('reason') }}</textarea>
                                </div>
                                <button class="btn btn-primary" type="submit">Simpan status</button>
                            </form>
                        </section>
                    @endif

                    <section class="admin-dashboard-card order-admin-card">
                        <h2 class="h5 mb-3">Riwayat perubahan</h2>
                        @forelse($order->statusHistories as $history)
                            @php
                                $historyStatus = \App\OrderStatus::tryFrom($history->new_status);
                            @endphp
                            <div class="order-admin-history-event">
                                <span class="order-status-badge {{ $historyStatus?->badgeClass() ?? 'order-status-unknown' }}">
                                    {{ $historyStatus?->label() ?? ucfirst($history->new_status) }}
                                </span>
                                <p class="small text-muted mt-2 mb-1">{{ $history->created_at->format('d M Y, H:i') }}</p>
                                <p class="small mb-1">{{ $history->changedBy?->name ?? 'Sistem' }}</p>
                                @if($history->reason)
                                    <p class="small mb-0"><strong>Alasan:</strong> {{ $history->reason }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-0">Belum ada riwayat status.</p>
                        @endforelse
                    </section>
                </div>
            </div>
        </div>
    </main>
@endsection
