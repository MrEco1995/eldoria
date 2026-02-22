<x-admin.layout title="Admin Dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Admin Dashboard</h1>
            <div class="text-muted small">Eingeloggt als {{ auth('admin')->user()->email }}</div>
        </div>
        <form method="post" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger">Logout</button>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h2 class="h5">Bereich aktiv</h2>
            <p class="mb-0 text-muted">
                Dieser Bereich ist ausschließlich über den Admin-Guard zugänglich.
                Normale User-Sessions haben hier keinen Zugriff.
            </p>
        </div>
    </div>
</x-admin.layout>
