<x-app-layout>
    <x-slot name="title">My Sales Pages</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Sales Pages</h1>
            <p class="text-gray-500 text-sm mt-0.5">{{ $pages->total() }} page(s) generated</p>
        </div>
        <a href="{{ route('sales-pages.create') }}" class="btn-primary">
            <i class="bi bi-plus-circle" aria-hidden="true"></i> Generate New
        </a>
    </div>

    @if($pages->isEmpty())
        <div class="card text-center py-16">
            <div class="text-5xl mb-4" aria-hidden="true"><i class="bi bi-file-earmark-text text-gray-300"></i></div>
            <h2 class="text-xl font-bold text-gray-700 mb-2">No pages yet</h2>
            <p class="text-gray-400 mb-6">Generate your first AI sales page in under 30 seconds.</p>
            <a href="{{ route('sales-pages.create') }}" class="btn-primary inline-flex">
                <i class="bi bi-plus-circle" aria-hidden="true"></i> Create First Page
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($pages as $page)
                <div class="card hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-3">
                        <span @class([
                            'text-xs font-semibold px-2.5 py-1 rounded-full',
                            'bg-gray-900 text-white' => $page->style === 'bold',
                            'bg-gray-100 text-gray-700' => $page->style === 'minimal',
                            'bg-brand/10 text-brand' => $page->style === 'modern',
                        ])>
                            {{ ucfirst($page->style) }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $page->created_at->diffForHumans() }}</span>
                    </div>

                    <h3 class="font-bold text-gray-900 mb-1 truncate">{{ $page->product_name }}</h3>
                    <p class="text-sm text-gray-500 mb-1 truncate">{{ $page->generated_data['headline'] ?? '' }}</p>
                    <p class="text-xs text-gray-400 mb-4">{{ $page->target_audience }}</p>

                    <div class="flex gap-2">
                        <a href="{{ route('sales-pages.show', $page) }}" class="btn-primary flex-1 justify-center text-sm py-2">
                            <i class="bi bi-eye" aria-hidden="true"></i> Preview
                        </a>
                        <a href="{{ route('sales-pages.export', $page) }}" class="btn-secondary text-sm py-2 px-3" title="Export HTML" aria-label="Export HTML">
                            <i class="bi bi-download" aria-hidden="true"></i>
                        </a>
                        <form method="POST" action="{{ route('sales-pages.destroy', $page) }}"
                            onsubmit="return confirm('Delete this page?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger text-sm py-2 px-3" title="Delete" aria-label="Delete">
                                <i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $pages->links() }}</div>
    @endif
</x-app-layout>
