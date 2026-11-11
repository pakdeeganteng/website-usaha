<footer class="site-footer pt-5 pb-4">
    <div class="container">
        <div class="row g-4 pb-4">
            <div class="col-lg-5">
                <a class="navbar-brand d-inline-flex align-items-center gap-2 mb-3" href="{{ route('home') }}">
                    <span class="brand-mark"><i class="bi bi-house-heart-fill" aria-hidden="true"></i></span>
                    <span>{{ data_get($businessSettings, 'name', config('app.name', 'DapurHarian')) }}</span>
                </a>
                <p class="mb-0" style="max-width: 24rem;">Menyajikan masakan rumahan yang hangat, dibuat dari bahan pilihan dan disiapkan penuh perhatian.</p>
            </div>

            <div class="col-6 col-lg-3">
                <h2 class="footer-heading h6 fw-bold mb-3">Jelajahi</h2>
                <ul class="list-unstyled d-grid gap-2 mb-0">
                    <li><a class="footer-link" href="{{ route('menu.index') }}">Daftar menu</a></li>
                    <li><a class="footer-link" href="{{ route('about') }}">Tentang usaha</a></li>
                    <li><a class="footer-link" href="{{ route('contact') }}">Hubungi kami</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-4">
                <h2 class="footer-heading h6 fw-bold mb-3">Informasi</h2>
                <ul class="list-unstyled d-grid gap-2 mb-0">
                    @if(data_get($businessSettings, 'phone'))
                        <li><i class="bi bi-telephone me-2" aria-hidden="true"></i><a class="footer-link" href="tel:{{ preg_replace('/\D+/', '', (string) data_get($businessSettings, 'phone', '')) }}">{{ data_get($businessSettings, 'phone') }}</a></li>
                    @endif
                    @if(data_get($businessSettings, 'address'))
                        <li><i class="bi bi-geo-alt me-2" aria-hidden="true"></i>{{ data_get($businessSettings, 'address') }}</li>
                    @endif
                    @if(data_get($businessSettings, 'email'))
                        <li><i class="bi bi-envelope me-2" aria-hidden="true"></i><a class="footer-link" href="mailto:{{ data_get($businessSettings, 'email') }}">{{ data_get($businessSettings, 'email') }}</a></li>
                    @endif
                    @if(data_get($businessSettings, 'opening_hours'))
                        <li><i class="bi bi-clock me-2" aria-hidden="true"></i>{{ data_get($businessSettings, 'opening_hours') }}</li>
                    @endif
                    @if($whatsappUrl)
                        <li><i class="bi bi-whatsapp me-2" aria-hidden="true"></i><a class="footer-link" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">WhatsApp kami</a></li>
                    @endif
                    @unless(data_get($businessSettings, 'phone') || data_get($businessSettings, 'address') || data_get($businessSettings, 'email') || data_get($businessSettings, 'opening_hours') || $whatsappUrl)
                        <li>Informasi kontak akan segera tersedia.</li>
                    @endunless
                </ul>
            </div>
        </div>

        <hr class="footer-divider">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 pt-2 small">
            <span>&copy; {{ now()->year }} {{ data_get($businessSettings, 'name', config('app.name', 'DapurHarian')) }}. Dibuat dengan sepenuh hati.</span>
            <a class="footer-link" href="{{ route('contact') }}">Ada pertanyaan? Hubungi kami <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
        </div>
    </div>
</footer>
