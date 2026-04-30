<x-app-layout>
    <x-slot name="title">Settings</x-slot>

    <div class="max-w-xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Settings</h1>
            <p class="text-gray-500 text-sm mt-1">Manage application branding and preferences.</p>
        </div>

        <div class="card">
            <h2 class="text-base font-bold text-gray-800 mb-4">
                <i class="bi bi-image" aria-hidden="true"></i> Site Logo
            </h2>

            @php $logoPath = \App\Models\Setting::get('site_logo'); @endphp

            @if($logoPath)
                <div class="mb-4 p-3 bg-gray-50 rounded-xl border border-gray-200 inline-block">
                    <p class="text-xs text-gray-400 mb-2">Current logo</p>
                    <img src="{{ asset('storage/' . $logoPath) }}" alt="Site logo" class="h-20 object-contain">
                </div>
            @else
                <div class="mb-4 p-3 bg-gray-50 rounded-xl border border-gray-200 inline-block text-gray-400 text-sm">
                    <i class="bi bi-image text-2xl block mb-1" aria-hidden="true"></i>
                    Using default Laravel logo
                </div>
            @endif

            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                @csrf @method('PATCH')

                <div class="mb-4">
                    <label for="site_logo" class="label">Upload New Logo</label>
                    <input id="site_logo" type="file" name="site_logo" accept="image/jpeg,image/png,image/gif,image/webp"
                        class="block w-full text-sm text-gray-700 border border-gray-200 rounded-xl px-4 py-2.5
                               file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                               file:text-sm file:font-semibold file:bg-brand/10 file:text-brand
                               hover:file:bg-brand/20 cursor-pointer">
                    <p class="text-xs text-gray-400 mt-1.5">JPG, PNG, GIF, WebP. Max 2MB.</p>
                    @error('site_logo')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary">
                    <i class="bi bi-upload" aria-hidden="true"></i> Save Logo
                </button>
            </form>

            @if($logoPath)
                <form method="POST" action="{{ route('settings.destroy-logo') }}" class="mt-3"
                    onsubmit="return confirm('Remove custom logo and restore default?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger text-sm py-2">
                        <i class="bi bi-trash" aria-hidden="true"></i> Remove Logo
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
