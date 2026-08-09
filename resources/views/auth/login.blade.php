@extends('layouts.app')

@section('title', 'Login Admin')

@section('main_class', 'container-fluid px-3 px-lg-4')

@section('content')
    <section class="auth-shell">
    <div class="row justify-content-center m-0 w-100">
        <div class="col-md-8 col-lg-5">
            <div class="card auth-card">
                <div class="p-4 p-lg-5">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="metric-icon"><i class="bi bi-shield-lock"></i></span>
                    <div>
                        <h1 class="h3 fw-bold mb-1">Login Admin</h1>
                        <p class="muted mb-0">Selamat datang kembali di ruang kontrol akuariummu.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger rounded-4 border-0">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" class="form-control" type="password" name="password" autocomplete="current-password" required>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                        <label class="form-check-label muted" for="remember">Ingat sesi login</label>
                    </div>

                    <button class="ui-btn ui-btn-primary w-100" type="submit">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Masuk Dashboard
                    </button>
                </form>

                <div class="small muted mt-4 text-center">
                    Belum punya akun?
                    <a class="fw-bold text-decoration-none" href="{{ route('register') }}">Daftar admin baru</a>
                </div>
                </div>
            </div>
        </div>
    </div>
    </section>
@endsection
