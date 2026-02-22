<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Adminbereich' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-shell {
            min-height: 100vh;
        }
        .admin-sidebar {
            flex: 0 0 10%;
            max-width: 10%;
            min-width: 120px;
            background: #111827;
            color: #f9fafb;
        }
        .admin-content {
            flex: 0 0 90%;
            max-width: 90%;
            background: #f8fafc;
        }
        .admin-nav-link {
            display: block;
            width: 100%;
            padding: 0.65rem 0.75rem;
            border-radius: 0.5rem;
            color: #d1d5db;
            text-decoration: none;
        }
        .admin-nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
        }
        .admin-nav-link.active {
            color: #fff;
            background: #2563eb;
        }
        @media (max-width: 992px) {
            .admin-shell {
                flex-direction: column;
            }
            .admin-sidebar,
            .admin-content {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .admin-sidebar {
                min-width: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
    @if (auth('admin')->check())
        <div class="d-flex admin-shell">
            <aside class="admin-sidebar p-3 d-flex flex-column">
                <div class="small text-uppercase fw-semibold opacity-75 mb-3">Admin</div>

                <nav class="d-flex flex-column gap-1">
                    <a href="{{ route('admin.users.index') }}" class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        User
                    </a>
                    <a href="{{ route('admin.characters.index') }}" class="admin-nav-link {{ request()->routeIs('admin.characters.*') ? 'active' : '' }}">
                        Charakter
                    </a>
                    <a href="{{ route('admin.races.index') }}" class="admin-nav-link {{ request()->routeIs('admin.races.*') ? 'active' : '' }}">
                        Völker
                    </a>
                    <a href="{{ route('admin.classes.index') }}" class="admin-nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}">
                        Klassen
                    </a>
                </nav>

                <div class="mt-auto pt-3">
                    <form method="post" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light w-100">Logout</button>
                    </form>
                </div>
            </aside>

            <main class="admin-content p-3 p-md-4">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>
    @else
        <main class="container py-4 py-md-5">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{ $slot }}
        </main>
    @endif
</body>
</html>
