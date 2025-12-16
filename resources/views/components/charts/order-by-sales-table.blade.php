{{-- Component: Order By Sales Table --}}
<div x-data="{
    currentYear: new Date().getFullYear(),
    currentMonth: new Date().getMonth() + 1,
    selectedYear: {{ request('sales_year', now()->year) }},
    selectedMonth: {{ request('sales_month', now()->month) }},
    
    get monthYearLabel() {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                           'July', 'August', 'September', 'October', 'November', 'December'];
        return monthNames[this.selectedMonth - 1] + ' ' + this.selectedYear;
    },
    
    previousMonth() {
        if (this.selectedMonth === 1) {
            this.selectedMonth = 12;
            this.selectedYear--;
        } else {
            this.selectedMonth--;
        }
        this.navigateToMonth();
    },
    
    nextMonth() {
        if (this.selectedMonth === 12) {
            this.selectedMonth = 1;
            this.selectedYear++;
        } else {
            this.selectedMonth++;
        }
        this.navigateToMonth();
    },
    
    resetToCurrentMonth() {
        this.selectedYear = this.currentYear;
        this.selectedMonth = this.currentMonth;
        this.navigateToMonth();
    },
    
    navigateToMonth() {
        const params = new URLSearchParams(window.location.search);
        params.set('sales_year', this.selectedYear);
        params.set('sales_month', this.selectedMonth);
        params.delete('sales_page'); // Reset to page 1 on month change
        
        const url = '{{ route('owner.dashboard') }}?' + params.toString();
        
        // Show loading
        NProgress.start();
        
        // Fetch and update only the sales table section
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newSection = doc.getElementById('sales-table-section');
            
            if (newSection) {
                document.getElementById('sales-table-section').outerHTML = newSection.outerHTML;
                
                // Update URL without reload
                window.history.pushState({}, '', url);
                
                // Re-initialize Alpine component
                if (window.Alpine) {
                    Alpine.initTree(document.getElementById('sales-table-section'));
                }
            }
            
            NProgress.done();
        })
        .catch(error => {
            console.error('Error:', error);
            NProgress.done();
        });
    }
}" id="sales-table-section" class="bg-white border border-gray-200 rounded-lg p-4 md:p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-2">
        <h3 class="text-center md:text-left text-lg font-bold text-gray-900">Order By Sales</h3>
        
        <div class="flex items-center justify-center sm:justify-start gap-2 sm:gap-3">
            <button @click="previousMonth()" class="p-1.5 bg-primary-light hover:bg-primary/10 rounded-md transition-colors">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            
            <span class="text-xs sm:text-sm font-medium text-gray-700 min-w-[110px] sm:min-w-[130px] text-center" x-text="monthYearLabel"></span>
            
            <button @click="nextMonth()" class="p-1.5 bg-primary-light hover:bg-primary/10 rounded-md transition-colors">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            
            <button @click="resetToCurrentMonth()" class="px-2.5 py-1.5 sm:px-3 text-xs sm:text-sm font-medium text-white bg-primary hover:bg-primary-dark rounded-md transition-colors">
                Reset
            </button>
        </div>
    </div>
    
    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-primary-light text-gray-600">
                <tr>
                    <th class="py-3 px-4 text-left font-medium rounded-l-lg">Sales</th>
                    <th class="py-3 px-4 text-center font-medium">Total Orders</th>
                    <th class="py-3 px-4 text-center font-medium">Total QTY</th>
                    <th class="py-3 px-4 text-right font-medium rounded-r-lg">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesData as $sale)
                    <tr class="hover:bg-gray-50 border-b border-gray-200 transition-colors">
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-semibold text-blue-600">
                                        {{ strtoupper(substr($sale->sales_name, 0, 1)) }}
                                    </span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $sale->sales_name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-center">
                            <span class="text-sm text-gray-700">{{ number_format($sale->total_orders) }}</span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-center">
                            <span class="text-sm text-gray-700">{{ number_format($sale->total_qty) }}</span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-right">
                            <span class="text-sm text-gray-700">Rp {{ number_format($sale->revenue, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500">No data available</p>
                                <p class="text-xs text-gray-400 mt-1">Try selecting a different month</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    {{-- Pagination --}}
    @if($salesData->hasPages())
        <div class="mt-5" id="sales-pagination-container">
            <x-custom-pagination :paginator="$salesData" />
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    setupSalesPagination();
});

function setupSalesPagination() {
    const paginationContainer = document.getElementById('sales-pagination-container');
    if (!paginationContainer) return;
    
    paginationContainer.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]');
        if (!link || !link.href.includes('sales_page=')) return;
        
        e.preventDefault();
        
        // Show loading bar
        NProgress.start();
        
        // Fetch and update only the sales table section
        fetch(link.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newSection = doc.getElementById('sales-table-section');
            
            if (newSection) {
                document.getElementById('sales-table-section').outerHTML = newSection.outerHTML;
                
                // Update URL without reload
                window.history.pushState({}, '', link.href);
                
                // Re-setup pagination listener
                setupSalesPagination();
                
                // Re-initialize Alpine component
                if (window.Alpine) {
                    Alpine.initTree(document.getElementById('sales-table-section'));
                }
            }
            
            NProgress.done();
        })
        .catch(error => {
            console.error('Error:', error);
            NProgress.done();
        });
    });
}
</script>
@endpush
