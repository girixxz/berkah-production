@extends('layouts.app')
@section('title', 'Manage Sales')
@section('content')
    <x-nav-locate :items="['Menu', 'Manage Data', 'Sales']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        openModal: '{{ session('openModal') }}',
        editSales: {},
        searchSales: '',
        showDeleteSalesConfirm: null,
    
        // Add Sales Validation
        addSalesForm: {
            sales_name: '',
            phone: ''
        },
        addSalesErrors: {},
    
        validateAddSales() {
            this.addSalesErrors = {};
    
            if (!this.addSalesForm.sales_name) {
                this.addSalesErrors.sales_name = 'Sales Name is required';
            } else if (this.addSalesForm.sales_name.length > 100) {
                this.addSalesErrors.sales_name = 'Sales Name must not exceed 100 characters';
            }
    
            if (this.addSalesForm.phone && this.addSalesForm.phone.length > 100) {
                this.addSalesErrors.phone = 'Phone must not exceed 100 characters';
            }
    
            return Object.keys(this.addSalesErrors).length === 0;
        },
    
        init() {
            this.$watch('openModal', value => {
                if (value) {
                    setTimeout(() => {
                        const modalEl = document.querySelector('[x-show=\'openModal === \\\'' + value + '\\\'\']');
                        if (modalEl) {
                            modalEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 100);
                }
    
                // Reset form saat modal dibuka
                if (value === 'addSales') {
                    this.addSalesForm = {
                        sales_name: '',
                        phone: ''
                    };
                    this.addSalesErrors = {};
                }
            });
        }
    }">

        {{-- ===================== SALES ===================== --}}
        <section id="sales-section" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <h2 class="text-xl font-semibold text-gray-900">Sales</h2>

                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="flex-1">
                        <div class="relative">
                            <x-icons.search />
                            <input type="text" x-model="searchSales" placeholder="Search Sales"
                                class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>
                    </div>

                    {{-- Add Sales --}}
                    <button @click="openModal = 'addSales'"
                        class="cursor-pointer flex-shrink-0 w-32 whitespace-nowrap px-3 py-2 rounded-md bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add Sales
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-178 overflow-y-auto">
                    <table class="min-w-[450px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">Sales Name</th>
                                <th class="py-2 px-4 text-left">Phone</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody id="sales-tbody">
                            @forelse ($sales as $sale)
                                <tr class="border-t border-gray-200"
                                    x-show="
                                        {{ Js::from(strtolower($sale->sales_name . ' ' . ($sale->phone ?? ''))) }}
                                        .includes(searchSales.toLowerCase())
                                    ">
                                    <td class="py-2 px-4">
                                        {{ ($sales->currentPage() - 1) * $sales->perPage() + $loop->iteration }}
                                    </td>
                                    <td class="py-2 px-4">{{ $sale->sales_name }}</td>
                                    <td class="py-2 px-4">{{ $sale->phone ?? '-' }}</td>

                                    <td class="py-2 px-4 text-right">
                                        <div class="relative inline-block text-left" x-data="{
                                            open: false,
                                            dropdownStyle: {},
                                            checkPosition() {
                                                const button = this.$refs.button;
                                                const rect = button.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                const spaceAbove = rect.top;
                                                const dropUp = spaceBelow < 200 && spaceAbove > spaceBelow;
                                        
                                                // Position fixed dropdown
                                                if (dropUp) {
                                                    this.dropdownStyle = {
                                                        position: 'fixed',
                                                        top: (rect.top - 90) + 'px',
                                                        left: (rect.right - 160) + 'px',
                                                        width: '160px'
                                                    };
                                                } else {
                                                    this.dropdownStyle = {
                                                        position: 'fixed',
                                                        top: (rect.bottom + 8) + 'px',
                                                        left: (rect.right - 160) + 'px',
                                                        width: '160px'
                                                    };
                                                }
                                            }
                                        }"
                                            x-init="$watch('open', value => {
                                                if (value) {
                                                    const scrollContainer = $el.closest('.overflow-y-auto');
                                                    const mainContent = document.querySelector('main');
                                                    const closeOnScroll = () => { open = false; };
                                            
                                                    scrollContainer?.addEventListener('scroll', closeOnScroll);
                                                    mainContent?.addEventListener('scroll', closeOnScroll);
                                                    window.addEventListener('resize', closeOnScroll);
                                                }
                                            })">
                                            {{-- Tombol Titik 3 Horizontal --}}
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>

                                            {{-- Dropdown Menu with Fixed Position --}}
                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[9999]">
                                                <div class="py-1">
                                                    {{-- Edit --}}
                                                    <button
                                                        @click="editSales = {{ $sale->toJson() }}; openModal = 'editSales'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>

                                                    {{-- Delete --}}
                                                    <button
                                                        @click="showDeleteSalesConfirm = {{ $sale->id }}; open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Sales found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination Sales --}}
            <div class="mt-4" id="sales-pagination-container">
                <x-custom-pagination :paginator="$sales" />
            </div>
        </section>

        {{-- ===================== MODALS ===================== --}}
        {{-- ========== Add Sales Modal ========== --}}
        <div x-show="openModal === 'addSales'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-md">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Add New Sales</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>

                <form action="{{ route('owner.manage-data.sales.store') }}" method="POST"
                    @submit="if (!validateAddSales()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700">Sales Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="sales_name" x-model="addSalesForm.sales_name"
                            @blur="validateAddSales()"
                            :class="addSalesErrors.sales_name ||
                                {{ $errors->addSales->has('sales_name') ? 'true' : 'false' }} ?
                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm pr-10 border focus:outline-none focus:ring-2 text-gray-700">

                        {{-- Error icon di dalam input --}}
                        @if ($errors->addSales->has('sales_name'))
                            <span class="absolute right-3 top-[42px] -translate-y-1/2 text-red-500 pointer-events-none">
                                <x-icons.danger />
                            </span>
                        @endif

                        <p x-show="addSalesErrors.sales_name" x-text="addSalesErrors.sales_name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('sales_name', 'addSales')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone (optional)</label>
                        <input type="tel" name="phone" x-model="addSalesForm.phone" @blur="validateAddSales()"
                            placeholder="e.g., 081234567890"
                            :class="addSalesErrors.phone ? 'border-red-500' : 'border-gray-200'"
                            class="mt-1 w-full rounded-md border px-4 py-2 text-sm text-gray-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <p x-show="addSalesErrors.phone" x-text="addSalesErrors.phone" class="mt-1 text-sm text-red-600">
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="openModal=null"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark cursor-pointer">Save</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========== Edit Sales Modal ========== --}}
        <div x-show="openModal === 'editSales'" x-cloak x-init="@if (session('openModal') === 'editSales' && session('editSalesId')) editSales = {{ \App\Models\Sale::find(session('editSalesId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-md">
                <div class="flex justify-between items-center border-b  border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Sales</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>

                <form :action="`{{ route('owner.manage-data.sales.index') }}/${editSales.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700">Sales Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="sales_name" value="{{ old('sales_name') }}"
                            x-model="editSales.sales_name" required maxlength="100"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm pr-10 border {{ $errors->editSales->has('sales_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        {{-- Error icon di dalam input --}}
                        @if ($errors->editSales->has('sales_name'))
                            <span class="absolute right-3 top-[42px] -translate-y-1/2 text-red-500 pointer-events-none">

                                <x-icons.danger />
                            </span>
                        @endif

                        @error('sales_name', 'editSales')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" name="phone" x-model="editSales.phone" maxlength="100"
                            pattern="[0-9+\-\s()]+" placeholder="e.g., 081234567890"
                            class="mt-1 w-full rounded-md border border-gray-200 px-4 py-2 text-sm text-gray-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="openModal=null"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark cursor-pointer">Update</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ================= DELETE SALES CONFIRMATION MODAL ================= --}}
        <div x-show="showDeleteSalesConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity">
            <div @click.away="showDeleteSalesConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                {{-- Icon --}}
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                {{-- Title --}}
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                    Delete Sales?
                </h3>

                {{-- Message --}}
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this sales? This action cannot be undone and all sales data will be
                    permanently removed.
                </p>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteSalesConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form :action="`{{ route('owner.manage-data.sales.index') }}/${showDeleteSalesConfirm}`"
                        method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors">
                            Yes, Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- AJAX Pagination Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination();
        });

        document.addEventListener('turbo:load', function() {
            setupPagination();
        });

        function setupPagination() {
            const container = document.getElementById('sales-pagination-container');
            if (!container) return;

            container.addEventListener('click', function(e) {
                const link = e.target.closest('a[href*="page="]');
                if (!link) return;

                e.preventDefault();
                const url = link.getAttribute('href');

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        // Update the section content
                        const newSection = doc.getElementById('sales-section');
                        const currentSection = document.getElementById('sales-section');
                        if (newSection && currentSection) {
                            currentSection.innerHTML = newSection.innerHTML;
                        }

                        // Scroll to pagination (bottom of table)
                        const paginationContainer = document.getElementById('sales-pagination-container');
                        if (paginationContainer) {
                            paginationContainer.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }

                        // Re-setup pagination after update
                        setupPagination();
                    })
                    .catch(error => {
                        console.error('Error loading pagination:', error);
                    });
            });
        }
    </script>

@endsection
