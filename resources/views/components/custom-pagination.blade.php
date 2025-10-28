@if ($paginator->hasPages())
    <div class="flex flex-col items-center gap-3">
        {{-- Info Text --}}
        <div class="text-sm text-gray-600 mt-4">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} entries
        </div>

        {{-- Pagination Navigation --}}
        <div class="flex items-center gap-1">
            {{-- Previous Button --}}
            @if ($paginator->onFirstPage())
                <button disabled
                    class="w-9 h-9 flex items-center justify-center rounded-md text-gray-400 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none"
                        stroke="currentColor" stroke-width="3">
                        <path d="M36 24H12M20 16L12 24L20 32" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            @else
                <a href="{{ $paginator->appends(request()->except($paginator->getPageName()))->previousPageUrl() }}"
                    class="w-9 h-9 flex items-center justify-center rounded-md bg-white text-gray-600 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none"
                        stroke="currentColor" stroke-width="3">
                        <path d="M36 24H12M20 16L12 24L20 32" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            @endif

            {{-- Page Numbers --}}
            @php
                $start = max($paginator->currentPage() - 2, 1);
                $end = min($start + 4, $paginator->lastPage());
                $start = max($end - 4, 1);
            @endphp

            @if ($start > 1)
                <a href="{{ $paginator->appends(request()->except($paginator->getPageName()))->url(1) }}"
                    class="w-9 h-9 flex items-center justify-center rounded-md bg-white text-gray-600 hover:bg-gray-100 transition text-sm">
                    1
                </a>
                @if ($start > 2)
                    <span class="px-2 text-gray-400 text-sm">...</span>
                @endif
            @endif

            @for ($i = $start; $i <= $end; $i++)
                @if ($i == $paginator->currentPage())
                    <button
                        class="w-9 h-9 flex items-center justify-center rounded-md bg-primary text-white font-medium text-sm">
                        {{ $i }}
                    </button>
                @else
                    <a href="{{ $paginator->appends(request()->except($paginator->getPageName()))->url($i) }}"
                        class="w-9 h-9 flex items-center justify-center rounded-md bg-white text-gray-600 hover:bg-gray-100 transition text-sm">
                        {{ $i }}
                    </a>
                @endif
            @endfor

            @if ($end < $paginator->lastPage())
                @if ($end < $paginator->lastPage() - 1)
                    <span class="px-2 text-gray-400 text-sm">...</span>
                @endif
                <a href="{{ $paginator->appends(request()->except($paginator->getPageName()))->url($paginator->lastPage()) }}"
                    class="w-9 h-9 flex items-center justify-center rounded-md bg-white text-gray-600 hover:bg-gray-100 transition text-sm">
                    {{ $paginator->lastPage() }}
                </a>
            @endif

            {{-- Next Button --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->appends(request()->except($paginator->getPageName()))->nextPageUrl() }}"
                    class="w-9 h-9 flex items-center justify-center rounded-md bg-white text-gray-600 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none"
                        stroke="currentColor" stroke-width="3">
                        <path d="M12 24H36M28 16L36 24L28 32" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            @else
                <button disabled
                    class="w-9 h-9 flex items-center justify-center rounded-md text-gray-400 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" fill="none"
                        stroke="currentColor" stroke-width="3">
                        <path d="M12 24H36M28 16L36 24L28 32" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            @endif
        </div>
    </div>
@endif
