<x-admin.layout title="Admin Login">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">Admin Login</h1>
                    <p class="text-muted small mb-4">Nur Konten aus der Tabelle <code>admins</code> haben Zugriff.</p>

                    <form method="post" action="{{ route('admin.login.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">E-Mail</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                required
                                autofocus
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Passwort</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-4">
                            <input id="remember" name="remember" type="checkbox" class="form-check-input" value="1">
                            <label for="remember" class="form-check-label">Angemeldet bleiben</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Anmelden</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin.layout>
