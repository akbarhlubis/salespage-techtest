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

{{-- Regenerate section bar (Bonus Feature) --}}
<div class="card mb-4 bg-indigo-50 border-indigo-100">
    <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-2">✨ Regenerate Section</p>
    <div class="flex flex-wrap gap-2">
        @foreach(['headline' => '💬 Headline', 'sub_headline' => '📝 Sub-headline', 'description' => '📖 Description', 'benefits' => '⭐ Benefits', 'cta' => '🎯 CTA'] as $key => $label)
        <button onclick="regenerateSection('{{ $key }}')"
            class="text-xs bg-white border border-indigo-200 text-indigo-700 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition font-medium"
            id="regen-{{ $key }}">
            {{ $label }}
        </button>
        @endforeach
    </div>
</div>

{{-- Live Preview iframe --}}
<div class="rounded-2xl overflow-hidden border border-gray-200 shadow-lg" style="height:80vh">
    <iframe id="previewFrame" srcdoc="" class="w-full h-full border-0"></iframe>
</div>

<script>
    // Load the generated HTML into the iframe
    const html = {{ Js::from($salesPage->generated_html) }};
    document.getElementById('previewFrame').srcdoc = html;

    // Regenerate section via AJAX
    async function regenerateSection(section) {
        const btn = document.getElementById('regen-' + section);
        const orig = btn.textContent;
        btn.textContent = '⏳ Regenerating...';
        btn.disabled = true;

        try {
            const resp = await fetch('{{ route('sales-pages.regenerate-section', $salesPage) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ section })
            });

            const data = await resp.json();
            if (data.success) {
                // Reload the iframe with updated content
                location.reload();
            } else {
                alert('Regeneration failed: ' + data.message);
            }
        } catch(e) {
            alert('Error: ' + e.message);
        } finally {
            btn.textContent = orig;
            btn.disabled = false;
        }
    }
</script>
@endsection
