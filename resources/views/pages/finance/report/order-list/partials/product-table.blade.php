{{-- Product Type Table Component --}}
<div class="bg-white border border-gray-200 rounded-lg p-5 mb-6" data-table-type="{{ $productType }}">
    {{-- Header: Title Left, Search + Show Per Page Right --}}
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4 mb-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>

        {{-- Search + Show Per Page --}}
        <div class="flex gap-2 items-center xl:min-w-0">
            {{-- Search Box --}}
            <div class="flex-1 xl:min-w-[240px]">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" x-model="{{ $searchKey }}" placeholder="Search by Invoice, Customer, or Product..."
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
            </div>

            {{-- Show Per Page Dropdown --}}
            <div x-data="{
                open: false,
                perPage: {{ request('per_page', 25) }},
                options: [
                    { value: 5, label: '5' },
                    { value: 10, label: '10' },
                    { value: 15, label: '15' },
                    { value: 20, label: '20' },
                    { value: 25, label: '25' },
                    { value: 50, label: '50' },
                    { value: 100, label: '100' }
                ],
                get selected() {
                    return this.options.find(o => o.value === this.perPage) || this.options[4];
                },
                selectOption(option) {
                    this.perPage = option.value;
                    this.open = false;
                    this.applyPerPageFilter();
                },
                applyPerPageFilter() {
                    const params = new URLSearchParams(window.location.search);
                    params.set('per_page', this.perPage);
                    params.delete('page');
                    
                    const url = '{{ route('finance.report.order-list') }}?' + params.toString();
                    window.history.pushState({}, '', url);
                    
                    NProgress.start();
                    fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newSection = doc.getElementById('tables-section');
                        
                        if (newSection) {
                            document.getElementById('tables-section').innerHTML = newSection.innerHTML;
                        }
                        
                        NProgress.done();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        NProgress.done();
                    });
                }
            }" class="relative flex-shrink-0">
                <button type="button" @click="open = !open"
                    class="w-15 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
                        focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                    <span x-text="selected.label"></span>
                    <svg class="w-3 h-3 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false" x-cloak 
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95" 
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75" 
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute z-20 mt-1 w-18 bg-white border border-gray-200 rounded-md shadow-lg">
                    <ul class="max-h-60 overflow-y-auto py-1">
                        <template x-for="option in options" :key="option.value">
                            <li @click="selectOption(option)"
                                class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                :class="{ 'bg-primary/10 font-medium text-primary': perPage === option.value }">
                                <span x-text="option.label"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-primary-light text-gray-600">
                <tr>
                    <th class="py-3 px-4 text-left font-bold rounded-l-lg">No Invoice</th>
                    <th class="py-3 px-4 text-left font-bold">Customer</th>
                    <th class="py-3 px-4 text-left font-bold">Product</th>
                    <th class="py-3 px-4 text-left font-bold">QTY</th>
                    <th class="py-3 px-4 text-left font-bold">Total Bill</th>
                    <th class="py-3 px-4 text-left font-bold">Paid</th>
                    <th class="py-3 px-4 text-left font-bold">Remaining</th>
                    <th class="py-3 px-4 text-left font-bold">Report Date</th>
                    <th class="py-3 px-4 text-left font-bold">Status</th>
                    <th class="py-3 px-4 text-center font-bold rounded-r-lg">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $index => $report)
                    @php
                        $order = $report->order;
                        $invoice = $order->invoice ?? null;
                        $customer = $order->customer ?? null;
                        $productCategory = $order->productCategory->name ?? '-';
                        $totalQty = $order->orderItems->sum('qty');
                    @endphp
                    <tr data-invoice="{{ $invoice->invoice_no ?? '' }}"
                        data-customer="{{ $customer->customer_name ?? '' }}"
                        data-product="{{ $productCategory }}"
                        x-show="matchesSearch($el, '{{ $searchKey }}')"
                        class="hover:bg-gray-50 border-b border-gray-200 transition-colors">
                        
                        {{-- No Invoice --}}
                        <td class="py-3 px-4 text-[12px]">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-medium text-gray-900">{{ $invoice->invoice_no ?? '-' }}</span>
                                @if ($order->shipping_type)
                                    @if (strtolower($order->shipping_type) === 'pickup')
                                        <span class="px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded">PICKUP</span>
                                    @elseif (strtolower($order->shipping_type) === 'delivery')
                                        <span class="px-1.5 py-0.5 text-[10px] font-semibold text-blue-700 bg-blue-100 rounded">DELIVERY</span>
                                    @endif
                                @endif
                                @if (isset($order->priority) && strtolower($order->priority) === 'high')
                                    <span class="text-[10px] font-semibold text-red-600 italic">(HIGH)</span>
                                @endif
                            </div>
                        </td>

                        {{-- Customer --}}
                        <td class="py-3 px-4 text-[12px]">
                            <div>
                                <p class="text-gray-700">{{ $customer->customer_name ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $customer->phone ?? '-' }}</p>
                            </div>
                        </td>

                        {{-- Product --}}
                        <td class="py-3 px-4 text-[12px]">
                            <span class="text-gray-700">{{ $productCategory }}</span>
                        </td>

                        {{-- QTY --}}
                        <td class="py-3 px-4 text-[12px]">
                            <span class="text-gray-700">{{ number_format($totalQty) }}</span>
                        </td>

                        {{-- Total Bill --}}
                        <td class="py-3 px-4 text-[12px]">
                            <span class="text-gray-700">Rp {{ number_format($invoice->total_bill ?? 0, 0, ',', '.') }}</span>
                        </td>

                        {{-- Paid --}}
                        <td class="py-3 px-4 text-[12px]">
                            @php $paid = ($invoice->total_bill ?? 0) - ($invoice->amount_due ?? 0); @endphp
                            <span class="font-medium text-green-600">Rp {{ number_format($paid, 0, ',', '.') }}</span>
                        </td>

                        {{-- Remaining --}}
                        <td class="py-3 px-4 text-[12px]">
                            <span class="font-medium text-red-600">Rp {{ number_format($invoice->amount_due ?? 0, 0, ',', '.') }}</span>
                        </td>

                        {{-- Report Date --}}
                        <td class="py-3 px-4 text-[12px]">
                            <span class="text-gray-700">{{ $report->created_at ? \Carbon\Carbon::parse($report->created_at)->format('d M Y') : '-' }}</span>
                        </td>

                        {{-- Status --}}
                        <td class="py-3 px-4">
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'wip' => 'bg-blue-100 text-blue-800',
                                    'finished' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                                $statusClass = $statusClasses[$order->production_status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="px-2 py-1 rounded-full text-[12px] font-medium {{ $statusClass }}">
                                {{ strtoupper($order->production_status) }}
                            </span>
                        </td>

                        {{-- Action --}}
                        <td class="py-3 px-4 text-center">
                            <div class="relative inline-block text-left" x-data="{ 
                                open: false, 
                                dropdownStyle: {}, 
                                checkPosition() { 
                                    const button = this.$refs.button; 
                                    const rect = button.getBoundingClientRect(); 
                                    const spaceBelow = window.innerHeight - rect.bottom; 
                                    const spaceAbove = rect.top; 
                                    const dropUp = spaceBelow < 200 && spaceAbove > spaceBelow; 
                                    if (dropUp) { 
                                        this.dropdownStyle = { position: 'fixed', top: (rect.top - 200) + 'px', left: (rect.right - 160) + 'px', width: '160px' }; 
                                    } else { 
                                        this.dropdownStyle = { position: 'fixed', top: (rect.bottom + 8) + 'px', left: (rect.right - 160) + 'px', width: '160px' }; 
                                    } 
                                } 
                            }" @scroll.window="open = false" x-init="$watch('open', value => {
                                if (value) {
                                    const closeOnScroll = () => { open = false; };
                                    const scrollableContainer = document.querySelector('.overflow-x-auto');
                                    if (scrollableContainer) {
                                        scrollableContainer.addEventListener('scroll', closeOnScroll, { once: true, passive: true });
                                    }
                                    const mainContent = document.querySelector('main');
                                    if (mainContent) {
                                        mainContent.addEventListener('scroll', closeOnScroll, { once: true, passive: true });
                                    }
                                    window.addEventListener('scroll', closeOnScroll, { once: true, passive: true });
                                    window.addEventListener('resize', closeOnScroll, { once: true, passive: true });
                                }
                            })">
                                <button x-ref="button" @click="checkPosition(); open = !open" type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100 cursor-pointer">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" x-cloak :style="dropdownStyle" class="bg-white border border-gray-200 rounded-md shadow-lg z-50 py-1">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" target="_blank" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View Detail
                                    </a>
                                    @if(!($currentPeriod && $currentPeriod->isLocked()))
                                        <button type="button"
                                            @click="showEditModal = {{ $report->id }}; editReportId = {{ $report->id }}; editMonth = {{ \Carbon\Carbon::parse($report->period_start)->month }}; editYear = {{ \Carbon\Carbon::parse($report->period_start)->year }}; editProductType = '{{ $report->product_type }}'; editInvoiceNo = '{{ $invoice->invoice_no ?? '-' }}'; editCustomerName = '{{ $customer->customer_name ?? '-' }}'; editProductLabel = '{{ $productCategory }}'; editQty = '{{ number_format($totalQty) }}'; editError = ''; open = false"
                                            class="w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button type="button" @click="showDeleteConfirm = {{ $report->id }}; open = false" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-500">
                                <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-sm font-medium">No {{ strtolower($title) }} found for this period.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination - Always Visible --}}
    <div class="mt-4">
        <x-custom-pagination :paginator="$reports" />
    </div>

    {{-- AJAX Pagination Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupProductTablePagination_{{ str_replace('-', '_', $productType) }}();
        });

        function setupProductTablePagination_{{ str_replace('-', '_', $productType) }}() {
            const container = document.querySelector('[data-table-type="{{ $productType }}"]');
            if (!container) return;

            const paginationLinks = container.querySelectorAll('.pagination a');
            
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const url = this.href;
                    if (!url || url.includes('javascript:')) return;
                    
                    NProgress.start();
                    
                    fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newSection = doc.getElementById('tables-section');
                        
                        if (newSection) {
                            document.getElementById('tables-section').innerHTML = newSection.innerHTML;
                            
                            // Re-initialize all pagination scripts
                            setupProductTablePagination_t_shirt();
                            setupProductTablePagination_makloon();
                            setupProductTablePagination_hoodie_polo_jersey();
                            setupProductTablePagination_pants();
                            
                            // Smooth scroll to bottom
                            window.scrollTo({
                                top: document.body.scrollHeight,
                                behavior: 'smooth'
                            });
                        }
                        
                        NProgress.done();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        NProgress.done();
                    });
                });
            });
        }
    </script>
</div>
