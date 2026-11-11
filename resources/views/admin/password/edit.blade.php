@extends('layouts.admin')

@section('title', 'Ubah Kata Sandi')

@section('content')
    @include('partials.admin-navigation')

    <main class="admin-dashboard">
        <div class="container-fluid admin-container admin-password-container">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Keamanan akun</span>
                    <h1>Ubah kata sandi</h1>
                    <p>Gunakan kata sandi baru yang kuat dan tidak digunakan di layanan lain.</p>
                </div>
            </div>

            <section class="admin-dashboard-card admin-password-card" aria-labelledby="password-form-title">
                <h2 id="password-form-title" class="visually-hidden">Form ubah kata sandi</h2>

                @if($errors->any())
                    <div class="admin-alert admin-alert-error" role="alert">
                        <p class="fw-semibold mb-1">Periksa kembali data berikut:</p>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.password.update') }}" class="admin-form">
                    @csrf
                    @method('PUT')

                    @foreach([
                        'current_password' => ['Kata sandi saat ini', 'current-password'],
                        'password' => ['Kata sandi baru', 'new-password'],
                        'password_confirmation' => ['Konfirmasi kata sandi baru', 'new-password'],
                    ] as $field => [$label, $autocomplete])
                        <div class="admin-field">
                            <label for="{{ $field }}">{{ $label }}</label>
                            <div class="admin-password-field">
                                <input
                                    id="{{ $field }}"
                                    name="{{ $field }}"
                                    type="password"
                                    autocomplete="{{ $autocomplete }}"
                                    required
                                    data-password-input
                                >
                                <button
                                    type="button"
                                    class="admin-password-toggle"
                                    data-password-toggle
                                    aria-label="Tampilkan {{ strtolower($label) }}"
                                    aria-pressed="false"
                                >
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <p class="admin-password-hint">Minimal 12 karakter, mencakup huruf besar, huruf kecil, angka, dan simbol.</p>
                    <button class="btn btn-primary admin-submit" type="submit">
                        Simpan kata sandi baru <i class="bi bi-check2 ms-2" aria-hidden="true"></i>
                    </button>
                </form>
            </section>
        </div>
    </main>
@endsection
