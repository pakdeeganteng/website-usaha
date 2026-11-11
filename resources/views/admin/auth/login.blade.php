@extends('layouts.admin')

@section('title', 'Login Admin')

@section('content')
    <main class="admin-auth-wrap">
        <section class="admin-auth-card admin-login-card" aria-labelledby="admin-login-title">
            <header class="admin-login-brand">
                <a class="admin-login-logo" href="{{ route('home') }}" aria-label="{{ config('app.name', 'DapurHarian') }}">
                    <span class="admin-brand-mark"><i class="bi bi-house-heart-fill" aria-hidden="true"></i></span>
                </a>
                <p class="admin-login-business">{{ config('app.name', 'DapurHarian') }}</p>
            </header>

            <div class="admin-auth-heading admin-login-heading">
                <span class="admin-eyebrow admin-login-eyebrow">Area Administrator</span>
                <h1 id="admin-login-title" class="admin-login-title">SELAMAT DATANG</h1>
                <p class="admin-login-subtitle">Senang melihat Anda kembali.</p>
                <p class="admin-login-description">Silakan masuk untuk mengakses dashboard administrator.</p>
            </div>

            @if(session('status'))
                <div class="admin-alert admin-alert-success" role="status">{{ session('status') }}</div>
            @endif

            @if($errors->has('email'))
                <div class="admin-alert admin-alert-error admin-login-alert" role="alert">
                    <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                    <span>{{ $errors->first('email') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="admin-form admin-login-form" data-admin-login-form>
                @csrf

                <div class="admin-field">
                    <label for="email">Email</label>
                    <div class="admin-login-input-wrap">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
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
                            aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                            class="@error('email') is-invalid @enderror"
                            placeholder="nama@contoh.com"
                        >
                    </div>
                </div>

                <div class="admin-field">
                    <label for="password">Kata sandi</label>
                    <div class="admin-password-field admin-login-input-wrap">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="@error('password') is-invalid @enderror"
                            placeholder="Masukkan kata sandi"
                            data-password-input
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
                    @error('password')
                        <span class="admin-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <button class="btn btn-primary admin-submit admin-login-submit" type="submit" data-admin-submit>
                    <span class="spinner-border spinner-border-sm d-none" aria-hidden="true" data-admin-submit-spinner></span>
                    <span data-admin-submit-label>MASUK KE DASHBOARD</span>
                    <i class="bi bi-arrow-right" aria-hidden="true" data-admin-submit-arrow></i>
                </button>
                <span class="visually-hidden" role="status" aria-live="polite" data-admin-submit-status></span>
            </form>

            <a class="admin-back-link admin-login-back-link" href="{{ route('home') }}">
                <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Kembali ke website
            </a>

            <footer class="admin-login-footer">
                &copy; {{ now()->year }} {{ config('app.name', 'DapurHarian') }}. All Rights Reserved.
            </footer>
        </section>
    </main>
@endsection
