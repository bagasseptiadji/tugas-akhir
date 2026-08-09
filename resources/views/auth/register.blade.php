@extends('layouts.app')

@section('title', 'Daftar Admin')

@section('main_class', 'container-fluid px-3 px-lg-4')

@section('content')
    <section class="auth-shell py-4">
    <div class="row justify-content-center m-0 w-100">
        <div class="col-md-8 col-lg-5">
            <div class="card auth-card">
                <div class="p-4 p-lg-5">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="metric-icon"><i class="bi bi-person-plus"></i></span>
                    <div>
                        <h1 class="h3 fw-bold mb-1">Daftar Admin</h1>
                        <p class="muted mb-0">Buat akun untuk mulai menjaga kualitas air bersama SmartQua.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger rounded-4 border-0">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="name">Nama</label>
                        <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" class="form-control" type="password" name="password" autocomplete="new-password" required>
                        <div class="form-text muted">Minimal 8 karakter.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                        <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" autocomplete="new-password" required>
                    </div>

                    <button class="ui-btn ui-btn-primary w-100" type="submit">
                        <i class="bi bi-person-check me-1"></i>Daftar dan Masuk
                    </button>
                </form>

                <div class="small muted mt-4 text-center">
                    Sudah punya akun?
                    <a class="fw-bold text-decoration-none" href="{{ route('login') }}">Masuk di sini</a>
                </div>
                </div>
            </div>
        </div>
    </div>
    </section>
@endsection
