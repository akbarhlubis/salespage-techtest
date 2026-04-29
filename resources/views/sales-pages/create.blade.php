{{-- File: resources/views/sales-pages/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Generate Sales Page')

@section('content')
<div class="max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Generate Sales Page</h1>
        <p class="text-gray-500 text-sm mt-1">Fill in your product details — AI will write the entire page.</p>
    </div>

    <form method="POST" action="{{ route('sales-pages.store') }}" id="generateForm">
        @csrf

        {{-- Product Name --}}
        <div class="card mb-4">
            <label class="label">Product / Service Name *</label>
            <input type="text" name="product_name" class="input" value="{{ old('product_name') }}"
                placeholder="e.g. ProFocus — Productivity App" required>
        </div>

        {{-- Description --}}
        <div class="card mb-4">
            <label class="label">Description *</label>
            <textarea name="description" rows="4" class="input resize-none" required
                placeholder="Describe what your product does, the problem it solves, and who it's for...">{{ old('description') }}</textarea>
            <p class="text-xs text-gray-400 mt-1.5">Min 20 characters. More detail = better output.</p>
        </div>

        {{-- Features --}}
        <div class="card mb-4">
            <label class="label">Key Features *</label>
            <input type="text" name="features" class="input" value="{{ old('features') }}"
                placeholder="AI-powered, Real-time sync, Mobile app, 24/7 support, Export to PDF" required>
            <p class="text-xs text-gray-400 mt-1.5">Comma-separated list of features</p>
        </div>

        {{-- Target Audience + Price (2 col on desktop) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div class="card">
                <label class="label">Target Audience *</label>
                <input type="text" name="target_audience" class="input" value="{{ old('target_audience') }}"
                    placeholder="e.g. Freelance designers, SMEs, Students" required>
            </div>
            <div class="card">
                <label class="label">Price *</label>
                <input type="text" name="price" class="input" value="{{ old('price') }}"
                    placeholder="e.g. Rp 299.000/bulan or $29/month" required>
            </div>
        </div>

        {{-- USP --}}
        <div class="card mb-4">
            <label class="label">Unique Selling Points <span class="font-normal text-gray-400">(optional)</span></label>
            <textarea name="unique_selling_points" rows="2" class="input resize-none"
                placeholder="What makes you different from competitors?">{{ old('unique_selling_points') }}</textarea>
        </div>

        {{-- Style --}}
        <div class="card mb-6">
            <label class="label">Page Style</label>
            <div class="grid grid-cols-3 gap-3 mt-1">
                @foreach(['modern' => ['🎨', 'Modern', 'Clean & professional'], 'minimal' => ['⬜', 'Minimal', 'Black & white'], 'bold' => ['🔥', 'Bold', 'Dark & dramatic']] as $val => [$icon, $label, $desc])
                <label class="cursor-pointer">
                    <input type="radio" name="style" value="{{ $val }}" class="sr-only peer" {{ old('style', 'modern') === $val ? 'checked' : '' }}>
                    <div class="border-2 rounded-xl p-3 text-center transition peer-checked:border-brand peer-checked:bg-brand/5 border-gray-200 hover:border-gray-300">
                        <div class="text-2xl mb-1">{{ $icon }}</div>
                        <div class="font-semibold text-sm">{{ $label }}</div>
                        <div class="text-xs text-gray-400">{{ $desc }}</div>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" id="submitBtn" class="btn-primary w-full justify-center py-4 text-base">
            <span id="btnIcon">⚡</span>
            <span id="btnText">Generate Sales Page</span>
        </button>
        <p class="text-center text-xs text-gray-400 mt-2">Usually takes 10–20 seconds</p>
    </form>
</div>

<script>
document.getElementById('generateForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.classList.add('opacity-75');
    document.getElementById('btnIcon').textContent = '⏳';
    document.getElementById('btnText').textContent = 'AI is writing your page...';
});
</script>
@endsection
