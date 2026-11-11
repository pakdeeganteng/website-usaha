@extends('layouts.admin')

@section('title', 'Masuk')

@section('content')
    <main class="admin-auth-wrap">
        <section class="admin-auth-card" aria-labelledby="customer-login-title">
            <a class="admin-brand" href="{{ route('login') }}">
                <span class="admin-brand-mark"><i class="bi bi-house-heart-fill" aria-hidden="true"></i></span>
                <span>{{ data_get($businessSettings, 'name', config('app.name', 'DapurHarian')) }}</span>
            </a>

            <div class="admin-auth-heading">
                <span class="admin-eyebrow">Area pelanggan</span>
                <h1 id="customer-login-title">Masuk ke akun Anda</h1>
                <p>Silakan masuk untuk melihat menu dan informasi usaha.</p>
            </div>

            @if(session('status'))
                <div class="admin-alert admin-alert-success" role="status">{{ session('status') }}</div>
            @endif

            @if($errors->has('email'))
                <div class="admin-alert admin-alert-error" role="alert">{{ $errors->first('email') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="admin-form">
                @csrf

                <div class="admin-field">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        inputmode="email"
                        maxlength="255"
                        required
                        autofocus
                        class="@error('email') is-invalid @enderror"
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
                            autocomplete="current-password"
                            required
                            data-password-input
                            placeholder="Masukkan kata sandi"
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

                <button class="btn btn-primary admin-submit" type="submit">
                    Masuk <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                </button>
            </form>

            <p class="auth-switch-link">
                Belum memiliki akun?
                <a href="{{ route('register') }}">Daftar sekarang</a>
            </p>
        </section>
    </main>
@endsection
