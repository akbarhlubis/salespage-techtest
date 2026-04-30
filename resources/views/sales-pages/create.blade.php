<x-app-layout>
    <x-slot name="title">Generate Sales Page</x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Generate Sales Page</h1>
            <p class="text-gray-500 text-sm mt-1">Fill in your product details — AI will write the entire page.</p>
        </div>

        <form method="POST" action="{{ route('sales-pages.store') }}" id="generateForm">
            @csrf

            <div class="card mb-4">
                <label for="product_name" class="label">Product / Service Name *</label>
                <input id="product_name" type="text" name="product_name" class="input" value="{{ old('product_name') }}"
                    placeholder="e.g. ProFocus — Productivity App" required>
            </div>

            <div class="card mb-4">
                <label for="description" class="label">Description *</label>
                <textarea id="description" name="description" rows="4" class="input resize-none" required
                    placeholder="Describe what your product does, the problem it solves, and who it's for...">{{ old('description') }}</textarea>
                <p class="text-xs text-gray-400 mt-1.5">Min 20 characters. More detail = better output.</p>
            </div>

            <div class="card mb-4">
                <label for="features" class="label">Key Features *</label>
                <input id="features" type="text" name="features" class="input" value="{{ old('features') }}"
                    placeholder="AI-powered, Real-time sync, Mobile app, 24/7 support, Export to PDF" required>
                <p class="text-xs text-gray-400 mt-1.5">Comma-separated list of features</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div class="card">
                    <label for="target_audience" class="label">Target Audience *</label>
                    <input id="target_audience" type="text" name="target_audience" class="input" value="{{ old('target_audience') }}"
                        placeholder="e.g. Freelance designers, SMEs, Students" required>
                </div>
                <div class="card">
                    <label for="price" class="label">Price *</label>
                    <input id="price" type="text" name="price" class="input" value="{{ old('price') }}"
                        placeholder="e.g. Rp 299.000/bulan or $29/month" required>
                </div>
            </div>

            <div class="card mb-4">
                <label for="unique_selling_points" class="label">
                    Unique Selling Points <span class="font-normal text-gray-400">(optional)</span>
                </label>
                <textarea id="unique_selling_points" name="unique_selling_points" rows="2" class="input resize-none"
                    placeholder="What makes you different from competitors?">{{ old('unique_selling_points') }}</textarea>
            </div>

            <div class="card mb-6">
                <span class="label">Page Style</span>
                <div class="grid grid-cols-3 gap-3 mt-1">
                    @foreach([
                        'modern'  => ['bi-grid-1x2', 'Modern',  'Clean & professional'],
                        'minimal' => ['bi-circle-half', 'Minimal', 'Black & white'],
                        'bold'    => ['bi-lightning-charge-fill', 'Bold', 'Dark & dramatic'],
                    ] as $val => [$icon, $label, $desc])
                        <label class="cursor-pointer">
                            <input type="radio" name="style" value="{{ $val }}" class="sr-only peer"
                                {{ old('style', 'modern') === $val ? 'checked' : '' }}>
                            <div class="border-2 rounded-xl p-3 text-center transition peer-checked:border-brand peer-checked:bg-brand/5 border-gray-200 hover:border-gray-300">
                                <div class="text-2xl mb-1" aria-hidden="true"><i class="bi {{ $icon }}"></i></div>
                                <div class="font-semibold text-sm">{{ $label }}</div>
                                <div class="text-xs text-gray-400">{{ $desc }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" id="submitBtn" class="btn-primary w-full justify-center py-4 text-base">
                <i id="btnIcon" class="bi bi-lightning-charge-fill" aria-hidden="true"></i>
                <span id="btnText">Generate Sales Page</span>
            </button>
            <p class="text-center text-xs text-gray-400 mt-2">Usually takes 10–20 seconds</p>
        </form>
    </div>

    @push('scripts')
    <script>
        document.getElementById('generateForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.classList.add('opacity-75');
            document.getElementById('btnIcon').className = 'bi bi-hourglass-split';
            document.getElementById('btnText').textContent = 'Generating your page...';
        });
    </script>
    @endpush
</x-app-layout>
