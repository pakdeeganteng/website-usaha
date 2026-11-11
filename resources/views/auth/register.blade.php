@extends('layouts.admin')

@section('title', 'Daftar')

@section('content')
    <main class="admin-auth-wrap">
        <section class="admin-auth-card" aria-labelledby="customer-register-title">
            <a class="admin-brand" href="{{ route('register') }}">
                <span class="admin-brand-mark"><i class="bi bi-house-heart-fill" aria-hidden="true"></i></span>
                <span>{{ data_get($businessSettings, 'name', config('app.name', 'DapurHarian')) }}</span>
            </a>

            <div class="admin-auth-heading">
                <span class="admin-eyebrow">Bergabung bersama kami</span>
                <h1 id="customer-register-title">Buat akun pelanggan</h1>
                <p>Daftar untuk melanjutkan menjelajahi website DapurHarian.</p>
            </div>

            @if($errors->any())
                <div class="admin-alert admin-alert-error" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="admin-form">
                @csrf

                <div class="admin-field">
                    <label for="name">Nama lengkap</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        autocomplete="name"
                        maxlength="255"
                        required
                        autofocus
                        placeholder="Nama Anda"
                    >
                </div>

                <div class="admin-field">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        inputmode="email"
                        maxlength="255"
                        required
                        placeholder="nama@contoh.com"
                    >
                </div>

                <div class="admin-field">
                    <label for="password">Kata sandi</label>
                    <div class="admin-password-field">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            data-password-input
                            placeholder="Buat kata sandi yang kuat"
                        >
                        <button
                            type="button"
                            class="admin-password-toggle"
                            data-password-toggle
                            aria-label="Tampilkan kata sandi"
                            aria-pressed="false"
                        >
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="admin-field">
                    <label for="password_confirmation">Konfirmasi kata sandi</label>
                    <div class="admin-password-field">
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            data-password-input
                            placeholder="Ulangi kata sandi"
                        >
                        <button
                            type="button"
                            class="admin-password-toggle"
                            data-password-toggle
                            aria-label="Tampilkan konfirmasi kata sandi"
                            aria-pressed="false"
                        >
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <p class="admin-password-hint">Gunakan minimal 12 karakter dengan huruf besar, huruf kecil, angka, dan simbol.</p>
                <button class="btn btn-primary admin-submit" type="submit">
                    Buat akun <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                </button>
            </form>

            <p class="auth-switch-link">
                Sudah memiliki akun?
                <a href="{{ route('login') }}">Masuk di sini</a>
            </p>
        </section>
    </main>
@endsection
