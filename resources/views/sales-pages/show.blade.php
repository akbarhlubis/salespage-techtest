{{-- File: resources/views/sales-pages/show.blade.php --}}
@extends('layouts.app')
@section('title', $salesPage->product_name . ' — Preview')

@section('content')

{{-- Top action bar --}}
<div class="flex flex-wrap items-center gap-3 mb-4">
    <a href="{{ route('sales-pages.index') }}" class="btn-secondary text-sm py-2">
        ← Back
    </a>
    <h1 class="font-bold text-gray-900 flex-1 truncate">{{ $salesPage->product_name }}</h1>
    <a href="{{ route('sales-pages.export', $salesPage) }}" class="btn-secondary text-sm py-2">
        ↓ Export HTML
    </a>
    <form method="POST" action="{{ route('sales-pages.destroy', $salesPage) }}"
        onsubmit="return confirm('Delete this page?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn-danger text-sm py-2">🗑 Delete</button>
    </form>
</div>

{{-- Regenerate section bar --}}
<div class="card mb-4 bg-indigo-50 border-indigo-100">
    <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3">✨ Regenerate Section</p>
    <div class="flex flex-wrap gap-2" id="regenButtons">
        @foreach(['headline' => '💬 Headline', 'sub_headline' => '📝 Sub-headline', 'description' => '📖 Description', 'benefits' => '⭐ Benefits', 'cta' => '🎯 CTA'] as $key => $label)
        <button onclick="regenerateSection('{{ $key }}')"
            class="regen-btn text-xs bg-white border border-indigo-200 text-indigo-700 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition font-medium disabled:opacity-50"
            id="regen-{{ $key }}" data-label="{{ $label }}">
            {{ $label }}
        </button>
        @endforeach
    </div>
    {{-- Progress indicator --}}
    <div id="regenStatus" class="hidden mt-3 flex items-center gap-2 text-sm text-indigo-600">
        <div class="animate-spin w-4 h-4 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
        <span id="regenStatusText">Regenerating...</span>
    </div>
</div>

{{-- Live Preview iframe --}}
<div class="rounded-2xl overflow-hidden border border-gray-200 shadow-lg" style="height:80vh; position:relative;">
    {{-- Loading overlay --}}
    <div id="iframeLoading" class="absolute inset-0 bg-white flex items-center justify-center z-10 hidden">
        <div class="text-center">
            <div class="animate-spin w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full mx-auto mb-3"></div>
            <p class="text-sm text-gray-500">Updating preview...</p>
        </div>
    </div>
    <iframe id="previewFrame" srcdoc="" class="w-full h-full border-0"></iframe>
</div>

<script>
    // Load generated HTML into iframe on page load
    const generatedHtml = {{ Js::from($salesPage->generated_html) }};
    document.getElementById('previewFrame').srcdoc = generatedHtml;

    // Regenerate section — no page reload
    async function regenerateSection(section) {
        const btn = document.getElementById('regen-' + section);
        const label = btn.dataset.label;
        const allBtns = document.querySelectorAll('.regen-btn');

        // Disable all buttons
        allBtns.forEach(b => b.disabled = true);

        // Show status
        const status = document.getElementById('regenStatus');
        const statusText = document.getElementById('regenStatusText');
        status.classList.remove('hidden');
        statusText.textContent = `Regenerating ${label}...`;

        // Show iframe loading overlay
        document.getElementById('iframeLoading').classList.remove('hidden');

        try {
            const resp = await fetch('{{ route('sales-pages.regenerate-section', $salesPage) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ section })
            });

            const data = await resp.json();

            if (data.success && data.html) {
                // Update iframe directly — no reload!
                document.getElementById('previewFrame').srcdoc = data.html;
                statusText.textContent = `✓ ${label} updated!`;
                setTimeout(() => status.classList.add('hidden'), 2000);
            } else {
                statusText.textContent = `✗ Failed: ${data.message || 'Unknown error'}`;
                setTimeout(() => status.classList.add('hidden'), 3000);
            }
        } catch(e) {
            statusText.textContent = `✗ Error: ${e.message}`;
            setTimeout(() => status.classList.add('hidden'), 3000);
        } finally {
            // Re-enable all buttons
            allBtns.forEach(b => b.disabled = false);
            document.getElementById('iframeLoading').classList.add('hidden');
        }
    }
</script>
@endsection