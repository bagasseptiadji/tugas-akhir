@extends('layouts.app')

@section('title', 'Manajemen Token API')

@section('content')
    <div class="api-token-page">
    @if (session('plain_token'))
        <section class="app-card p-4 mb-4">
            <div class="d-flex align-items-start gap-3">
                <span class="metric-icon green"><i class="bi bi-key"></i></span>
                <div class="w-100">
                    <h2 class="h5 fw-bold mb-2">Token Baru</h2>
                    <p class="small muted mb-3">Token hanya ditampilkan sekali. Gunakan nilai ini pada header <code>Authorization: Bearer TOKEN</code>.</p>
                    <div class="d-flex gap-2 align-items-stretch">
                        <pre class="rounded-4 p-3 mb-0 flex-grow-1" style="background:#101820;color:#e8f3f7;overflow:auto;"><code id="plainTokenValue">{{ session('plain_token') }}</code></pre>
                        <button class="ui-btn align-self-start" type="button" data-copy-target="plainTokenValue">
                            <i class="bi bi-copy"></i>Copy
                        </button>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="app-card p-4 h-100">
                <div class="d-flex align-items-start gap-3">
                    <span class="metric-icon green"><i class="bi bi-broadcast"></i></span>
                    <div>
                        <div class="metric-label mb-2">Status API</div>
                        <strong>{{ $summary['api_status'] }}</strong>
                        <p class="small muted mb-0 mt-2">Endpoint siap menerima data ESP32.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="app-card p-4 h-100">
                <div class="d-flex align-items-start gap-3">
                    <span class="metric-icon"><i class="bi bi-key"></i></span>
                    <div>
                        <div class="metric-label mb-2">Token Aktif</div>
                        <strong>{{ $summary['active_tokens'] }}</strong>
                        <p class="small muted mb-0 mt-2">Token yang masih bisa dipakai alat.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="app-card p-4 h-100">
                <div class="d-flex align-items-start gap-3">
                    <span class="metric-icon orange"><i class="bi bi-clock-history"></i></span>
                    <div>
                        <div class="metric-label mb-2">Last Request</div>
                        <strong>{{ $summary['last_request'] ? $summary['last_request']->format('d M Y H:i') : '-' }}</strong>
                        <p class="small muted mb-0 mt-2">Terakhir token digunakan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3">
        <div class="col-lg-4">
            <div class="app-card p-4 h-100">
                <h2 class="h5 fw-bold mb-3">Buat Token</h2>
                <p class="small muted">Token dipasang pada header request ESP32: <code>Authorization: Bearer TOKEN</code>.</p>
                <div class="alert alert-light border rounded-4 small mb-3">
                    Token hanya ditampilkan sekali setelah dibuat. Simpan token dengan aman.
                </div>
                <form method="POST" action="{{ route('api-tokens.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold muted">Nama Token</label>
                        <input class="form-control" name="name" value="{{ old('name', 'ESP32 Akuarium Utama') }}" required>
                        @error('name')<div class="small text-danger mt-2">{{ $message }}</div>@enderror
                    </div>
                    <button class="ui-btn ui-btn-primary w-100" type="submit">
                        <i class="bi bi-plus-circle me-1"></i>Generate Token
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="app-card p-4">
                <h2 class="h5 fw-bold mb-3">Token Aktif</h2>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Dibuat</th>
                            <th>Terakhir Dipakai</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($tokens as $token)
                            <tr>
                                <td class="fw-semibold">{{ $token->name }}</td>
                                <td>{{ $token->created_at?->format('d M Y H:i') }}</td>
                                <td>{{ $token->last_used_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('api-tokens.destroy', $token) }}" class="js-token-revoke-form">
                                        @csrf
                                        @method('DELETE')
                                        <button class="ui-btn ui-btn-danger ui-btn-sm" type="submit">
                                            <i class="bi bi-trash me-1"></i>Cabut
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center muted py-4">Belum ada token API.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('submit', event => {
        const form = event.target.closest('.js-token-revoke-form');
        if (!form) return;

        event.preventDefault();
        window.appToast?.confirm('Cabut token ini?', 'Token tidak dapat digunakan lagi.', () => form.submit());
    });

    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-copy-target]');
        if (!button) return;
        const target = document.getElementById(button.dataset.copyTarget);
        if (!target) return;

        try {
            await navigator.clipboard.writeText(target.textContent.trim());
            button.innerHTML = '<i class="bi bi-check2"></i>Copied';
            window.appToast?.success('Token berhasil disalin.');
            setTimeout(() => button.innerHTML = '<i class="bi bi-copy"></i>Copy', 1600);
        } catch (error) {
            window.appToast?.error('Gagal menyalin token.');
        }
    });
</script>
@endpush
