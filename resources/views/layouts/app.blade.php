{{-- File: resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AI Sales Page Generator')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#6366f1', dark: '#4f46e5', light: '#e0e7ff' }
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-bg { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); }
        .card { @apply bg-white rounded-2xl shadow-sm border border-gray-100 p-6; }
        .btn-primary { @apply bg-brand text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-brand-dark transition-all duration-200 inline-flex items-center gap-2; }
        .btn-secondary { @apply bg-gray-100 text-gray-700 px-5 py-2.5 rounded-xl font-semibold hover:bg-gray-200 transition-all duration-200 inline-flex items-center gap-2; }
        .btn-danger { @apply bg-red-50 text-red-600 px-5 py-2.5 rounded-xl font-semibold hover:bg-red-100 transition-all duration-200 inline-flex items-center gap-2; }
        .input { @apply w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand focus:border-transparent transition-all; }
        .label { @apply block text-sm font-semibold text-gray-700 mb-1.5; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Navbar --}}
    <nav class="gradient-bg text-white sticky top-0 z-50 shadow-lg">
        <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
            <a href="{{ route('sales-pages.index') }}" class="font-bold text-lg flex items-center gap-2">
                ⚡ <span>SalesPage AI</span>
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('sales-pages.create') }}" class="bg-white/10 hover:bg-white/20 text-white text-sm px-4 py-1.5 rounded-lg transition">
                    + New
                </a>
                <span class="text-white/60 text-sm hidden sm:block">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-white/60 hover:text-white text-sm transition">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="max-w-6xl mx-auto px-4 mt-4">
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                ✓ {{ session('success') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="max-w-6xl mx-auto px-4 mt-4">
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
                @foreach($errors->all() as $error)
                    <div>⚠ {{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Page content --}}
    <main class="max-w-6xl mx-auto px-4 py-6">
        @yield('content')
    </main>

</body>
</html>
