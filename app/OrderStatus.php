<?php

namespace App;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Sudah Dipesan',
            self::Rejected => 'Ditolak',
        };
    }

    public function notificationTitle(): string
    {
        return match ($this) {
            self::Pending => 'Pesanan Sedang Diproses.',
            self::Confirmed => 'Pemesanan Berhasil!',
            self::Rejected => 'Pemesanan Ditolak.',
        };
    }

    public function notificationMessage(): string
    {
        return match ($this) {
            self::Pending => 'Pesanan Anda berhasil dikirim dan sedang menunggu konfirmasi admin.',
            self::Confirmed => 'Selamat! Pesanan Anda telah dikonfirmasi dan berhasil dipesan. Terima kasih telah menggunakan layanan kami.',
            self::Rejected => 'Maaf, pemesanan Anda belum dapat disetujui. Silakan hubungi admin untuk informasi lebih lanjut.',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'order-status-pending',
            self::Confirmed => 'order-status-confirmed',
            self::Rejected => 'order-status-rejected',
        };
    }
}
