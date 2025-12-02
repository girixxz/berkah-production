{{-- Component: Order By Sales Table --}}
<div class="bg-white border border-gray-200 rounded-lg p-6">
    {{-- Header --}}
    <div class="mb-5">
        <h3 class="text-lg font-semibold text-gray-900">Order By Sales</h3>
    </div>
    
    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-primary-light text-gray-600">
                <tr>
                    <th class="py-3 px-4 text-left font-medium rounded-l-lg">
                        Sales
                    </th>
                    <th class="py-3 px-4 text-center font-medium">
                        Total Orders
                    </th>
                    <th class="py-3 px-4 text-center font-medium">
                        Total QTY
                    </th>
                    <th class="py-3 px-4 text-right font-medium rounded-r-lg">
                        Revenue
                    </th>
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
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $sale->sales_name }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-center">
                            <span class="text-sm text-gray-700">
                                {{ number_format($sale->total_orders) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-center">
                            <span class="text-sm text-gray-700">
                                {{ number_format($sale->total_qty) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-right">
                            <span class="text-sm text-gray-700">
                                Rp {{ number_format($sale->revenue, 0, ',', '.') }}
                            </span>
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
                                <p class="text-xs text-gray-400 mt-1">Try adjusting the date filter</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    {{-- Summary Footer (Optional) --}}
    @if(count($salesData) > 0)
        <div class="mt-5 pt-4 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 text-sm">
                <span class="text-gray-600">Total: <span class="font-semibold text-gray-900">{{ count($salesData) }}</span> Sales</span>
                <div class="flex flex-wrap gap-4 sm:gap-6">
                    <span class="text-gray-600">
                        Orders: <span class="font-semibold text-gray-900">{{ number_format($salesData->sum('total_orders')) }}</span>
                    </span>
                    <span class="text-gray-600">
                        QTY: <span class="font-semibold text-gray-900">{{ number_format($salesData->sum('total_qty')) }}</span>
                    </span>
                    <span class="text-gray-600">
                        Revenue: <span class="font-semibold text-gray-900">Rp {{ number_format($salesData->sum('revenue'), 0, ',', '.') }}</span>
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>
