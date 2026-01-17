@extends('layouts.app')
@section('title', 'Manage Finance Data')
@section('content')

    <x-nav-locate :items="['Menu', 'Manage Data', 'Finance']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        openModal: '{{ session('openModal') }}',
        searchSupplier: '',
        searchPartner: '',
        searchFixCost: '',
        editSupplier: {},
        editPartner: {},
        editFixCost: {},
        showDeleteSupplierConfirm: null,
        showDeletePartnerConfirm: null,
        showDeleteFixCostConfirm: null,
        addSupplierForm: { supplier_name: '', notes: '' },
        addSupplierErrors: {},
        addPartnerForm: { partner_name: '', notes: '' },
        addPartnerErrors: {},
        addFixCostForm: { category: '', list_name: '' },
        addFixCostErrors: {},
    
        validateAddSupplier() {
            this.addSupplierErrors = {};
            if (!this.addSupplierForm.supplier_name) {
                this.addSupplierErrors.supplier_name = 'Supplier name is required';
            } else if (this.addSupplierForm.supplier_name.length > 100) {
                this.addSupplierErrors.supplier_name = 'Supplier name must not exceed 100 characters';
            }
            return Object.keys(this.addSupplierErrors).length === 0;
        },
    
        validateAddPartner() {
            this.addPartnerErrors = {};
            if (!this.addPartnerForm.partner_name) {
                this.addPartnerErrors.partner_name = 'Partner name is required';
            } else if (this.addPartnerForm.partner_name.length > 100) {
                this.addPartnerErrors.partner_name = 'Partner name must not exceed 100 characters';
            }
            return Object.keys(this.addPartnerErrors).length === 0;
        },
    
        validateAddFixCost() {
            this.addFixCostErrors = {};
            if (!this.addFixCostForm.category) {
                this.addFixCostErrors.category = 'Category is required';
            }
            if (!this.addFixCostForm.list_name) {
                this.addFixCostErrors.list_name = 'List name is required';
            } else if (this.addFixCostForm.list_name.length > 100) {
                this.addFixCostErrors.list_name = 'List name must not exceed 100 characters';
            }
            return Object.keys(this.addFixCostErrors).length === 0;
        },
    
        validateEditSupplier() { return true; },
        validateEditPartner() { return true; },
        validateEditFixCost() { return true; },
    
        init() {
            const scrollToSection = '{{ session('scrollToSection') }}';
            if (scrollToSection) {
                setTimeout(() => {
                    const section = document.getElementById(scrollToSection);
                    if (section) {
                        section.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 300);
            }
            
            this.$watch('openModal', value => {
                if (value) {
                    setTimeout(() => {
                        const modalEl = document.querySelector('[x-show=\'openModal === \\\'' + value + '\\\'\']');
                        if (modalEl) {
                            modalEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 100);
                }
    
                if (value === 'addSupplier') {
                    this.addSupplierForm = { supplier_name: '', notes: '' };
                    this.addSupplierErrors = {};
                } else if (value === 'addPartner') {
                    this.addPartnerForm = { partner_name: '', notes: '' };
                    this.addPartnerErrors = {};
                } else if (value === 'addFixCost') {
                    this.addFixCostForm = { category: '', list_name: '' };
                    this.addFixCostErrors = {};
                }
            });
        }
    }">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- ===================== Material Suppliers ===================== --}}
        <section id="material-suppliers" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Material Suppliers
                </h2>

                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchSupplier" placeholder="Search suppliers..."
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Show Per Page Dropdown --}}
                    <div x-data="{
                        open: false,
                        perPage: {{ request('per_page_supplier', 5) }},
                        options: [
                            { value: 5, label: '5' },
                            { value: 10, label: '10' },
                            { value: 15, label: '15' },
                            { value: 20, label: '20' },
                            { value: 25, label: '25' }
                        ],
                        get selected() {
                            return this.options.find(o => o.value === this.perPage) || this.options[0];
                        },
                        selectOption(option) {
                            this.perPage = option.value;
                            this.open = false;
                            this.applyPerPageFilter();
                        },
                        applyPerPageFilter() {
                            const params = new URLSearchParams(window.location.search);
                            params.set('per_page_supplier', this.perPage);
                            params.delete('suppliers_page');
                            
                            const url = '{{ route('owner.manage-data.finance.index') }}?' + params.toString();
                            window.history.pushState({}, '', url);
                            
                            fetch(url, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newSection = doc.getElementById('material-suppliers');
                                
                                if (newSection) {
                                    document.getElementById('material-suppliers').innerHTML = newSection.innerHTML;
                                    setupPagination('supplier-pagination-container', 'material-suppliers');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                        }
                    }" class="relative flex-shrink-0">
                        <button type="button" @click="open = !open"
                            class="w-14 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
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
                            class="absolute z-20 mt-1 w-14 bg-white border border-gray-200 rounded-md shadow-lg">
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

                    {{-- Add Button --}}
                    <button @click="openModal = 'addSupplier'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>

            {{-- Table Material Suppliers --}}
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-[300px] w-full text-sm">
                    <thead class="bg-primary-light text-font-base">
                        <tr>
                            <th class="py-2 px-4 text-left rounded-l-md">No</th>
                            <th class="py-2 px-4 text-left">Supplier Name</th>
                            <th class="py-2 px-4 text-left">Notes</th>
                            <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                        </tr>
                    </thead>
                    <tbody x-data="{
                            get hasResults() {
                                if (searchSupplier.trim() === '') return true;
                                const suppliers = [
                                    @foreach ($allMaterialSuppliers as $supplier)
                                        '{{ strtolower($supplier->supplier_name) }}',
                                    @endforeach
                                ];
                                return suppliers.some(name => name.includes(searchSupplier.toLowerCase()));
                            }
                        }">
                            {{-- Data Paginated --}}
                            @forelse ($materialSuppliers as $supplier)
                                <tr class="border-t border-gray-200" x-show="searchSupplier.trim() === ''">
                                    <td class="py-2 px-4">{{ $materialSuppliers->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">
                                        <div class="font-medium text-gray-900">{{ $supplier->supplier_name }}</div>
                                    </td>
                                    <td class="py-2 px-4">
                                        @if($supplier->notes)
                                            <div class="text-sm text-gray-600" title="{{ $supplier->notes }}">{{ Str::limit($supplier->notes, 40) }}</div>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
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
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[9999]">
                                                <div class="py-1">
                                                    <button
                                                        @click="editSupplier = {{ $supplier->toJson() }}; openModal = 'editSupplier'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>

                                                    <button type="button"
                                                        @click="showDeleteSupplierConfirm = {{ $supplier->id }}; open = false"
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
                                <tr x-show="searchSupplier.trim() === ''">
                                    <td colspan="4" class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Material Suppliers found.
                                    </td>
                                </tr>
                            @endforelse

                            {{-- Data ALL (untuk search) --}}
                            @foreach ($allMaterialSuppliers as $index => $supplier)
                                <tr class="border-t border-gray-200"
                                    x-show="searchSupplier.trim() !== '' && '{{ strtolower($supplier->supplier_name) }}'.includes(searchSupplier.toLowerCase())">
                                    <td class="py-2 px-4">{{ $index + 1 }}</td>
                                    <td class="py-2 px-4">
                                        <div class="font-medium text-gray-900">{{ $supplier->supplier_name }}</div>
                                    </td>
                                    <td class="py-2 px-4">
                                        @if($supplier->notes)
                                            <div class="text-sm text-gray-600" title="{{ $supplier->notes }}">{{ Str::limit($supplier->notes, 40) }}</div>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
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
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[9999]">
                                                <div class="py-1">
                                                    <button
                                                        @click="editSupplier = {{ $supplier->toJson() }}; openModal = 'editSupplier'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>

                                                    <button type="button"
                                                        @click="showDeleteSupplierConfirm = {{ $supplier->id }}; open = false"
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
                            @endforeach
                            
                            {{-- Empty state for search --}}
                            <tr x-show="searchSupplier.trim() !== '' && !hasResults" x-cloak>
                                <td colspan="4" class="py-8 px-4 text-center text-gray-500 border-t border-gray-200">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <p class="text-sm font-medium">No suppliers found</p>
                                        <p class="text-xs text-gray-400">Try searching with a different keyword</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <!-- Pagination -->
            <div x-show="searchSupplier.trim() === ''" id="supplier-pagination-container" class="mt-4">
                <x-custom-pagination :paginator="$materialSuppliers" />
            </div>
        </section>

        {{-- ===================== Support Partners ===================== --}}
        <section id="support-partners" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Support Partners
                </h2>

                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchPartner" placeholder="Search partners..."
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Show Per Page Dropdown --}}
                    <div x-data="{
                        open: false,
                        perPage: {{ request('per_page_partner', 5) }},
                        options: [
                            { value: 5, label: '5' },
                            { value: 10, label: '10' },
                            { value: 15, label: '15' },
                            { value: 20, label: '20' },
                            { value: 25, label: '25' }
                        ],
                        get selected() {
                            return this.options.find(o => o.value === this.perPage) || this.options[0];
                        },
                        selectOption(option) {
                            this.perPage = option.value;
                            this.open = false;
                            this.applyPerPageFilter();
                        },
                        applyPerPageFilter() {
                            const params = new URLSearchParams(window.location.search);
                            params.set('per_page_partner', this.perPage);
                            params.delete('partners_page');
                            
                            const url = '{{ route('owner.manage-data.finance.index') }}?' + params.toString();
                            window.history.pushState({}, '', url);
                            
                            fetch(url, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newSection = doc.getElementById('support-partners');
                                
                                if (newSection) {
                                    document.getElementById('support-partners').innerHTML = newSection.innerHTML;
                                    setupPagination('partner-pagination-container', 'support-partners');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                        }
                    }" class="relative flex-shrink-0">
                        <button type="button" @click="open = !open"
                            class="w-14 flex justify-between items-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white
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
                            class="absolute z-20 mt-1 w-14 bg-white border border-gray-200 rounded-md shadow-lg">
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

                    {{-- Add Button --}}
                    <button @click="openModal = 'addPartner'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>

            {{-- Table Support Partners --}}
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-[300px] w-full text-sm">
                    <thead class="bg-primary-light text-font-base">
                        <tr>
                            <th class="py-2 px-4 text-left rounded-l-md">No</th>
                            <th class="py-2 px-4 text-left">Partner Name</th>
                            <th class="py-2 px-4 text-left">Notes</th>
                            <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                        </tr>
                    </thead>
                    <tbody x-data="{
                            get hasResults() {
                                if (searchPartner.trim() === '') return true;
                                const partners = [
                                    @foreach ($allSupportPartners as $partner)
                                        '{{ strtolower($partner->partner_name) }}',
                                    @endforeach
                                ];
                                return partners.some(name => name.includes(searchPartner.toLowerCase()));
                            }
                        }">
                            {{-- Data Paginated --}}
                            @forelse ($supportPartners as $partner)
                                <tr class="border-t border-gray-200" x-show="searchPartner.trim() === ''">
                                    <td class="py-2 px-4">{{ $supportPartners->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">
                                        <div class="font-medium text-gray-900">{{ $partner->partner_name }}</div>
                                    </td>
                                    <td class="py-2 px-4">
                                        @if($partner->notes)
                                            <div class="text-sm text-gray-600" title="{{ $partner->notes }}">{{ Str::limit($partner->notes, 40) }}</div>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
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
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[9999]">
                                                <div class="py-1">
                                                    <button
                                                        @click="editPartner = {{ $partner->toJson() }}; openModal = 'editPartner'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>

                                                    <button type="button"
                                                        @click="showDeletePartnerConfirm = {{ $partner->id }}; open = false"
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
                                <tr x-show="searchPartner.trim() === ''">
                                    <td colspan="4" class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Support Partners found.
                                    </td>
                                </tr>
                            @endforelse

                            {{-- Data ALL (untuk search) --}}
                            @foreach ($allSupportPartners as $index => $partner)
                                <tr class="border-t border-gray-200"
                                    x-show="searchPartner.trim() !== '' && '{{ strtolower($partner->partner_name) }}'.includes(searchPartner.toLowerCase())">
                                    <td class="py-2 px-4">{{ $index + 1 }}</td>
                                    <td class="py-2 px-4">
                                        <div class="font-medium text-gray-900">{{ $partner->partner_name }}</div>
                                    </td>
                                    <td class="py-2 px-4">
                                        @if($partner->notes)
                                            <div class="text-sm text-gray-600" title="{{ $partner->notes }}">{{ Str::limit($partner->notes, 40) }}</div>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
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
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[9999]">
                                                <div class="py-1">
                                                    <button
                                                        @click="editPartner = {{ $partner->toJson() }}; openModal = 'editPartner'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>

                                                    <button type="button"
                                                        @click="showDeletePartnerConfirm = {{ $partner->id }}; open = false"
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
                            @endforeach
                            
                            {{-- Empty state for search --}}
                            <tr x-show="searchPartner.trim() !== '' && !hasResults" x-cloak>
                                <td colspan="4" class="py-8 px-4 text-center text-gray-500 border-t border-gray-200">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <p class="text-sm font-medium">No partners found</p>
                                        <p class="text-xs text-gray-400">Try searching with a different keyword</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <!-- Pagination -->
            <div x-show="searchPartner.trim() === ''" id="partner-pagination-container" class="mt-4">
                <x-custom-pagination :paginator="$supportPartners" />
            </div>
        </section>

        {{-- ===================== Fix Cost Lists ===================== --}}
        <section id="fix-cost-lists" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Fix Cost Lists
                </h2>

                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchFixCost" placeholder="Search lists..."
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Show Per Page Dropdown --}}
                    <div x-data="{
                        open: false,
                        perPage: {{ request('per_page_fix_cost', 10) }},
                        options: [
                            { value: 5, label: '5' },
                            { value: 10, label: '10' },
                            { value: 15, label: '15' },
                            { value: 20, label: '20' },
                            { value: 25, label: '25' }
                        ],
                        changePerPage(value) {
                            const url = new URL(window.location.href);
                            url.searchParams.set('per_page_fix_cost', value);
                            window.location.href = url.toString();
                        }
                    }" class="relative flex-shrink-0">
                        <button @click="open = !open" type="button"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary/20">
                            <span x-text="perPage"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                            class="absolute right-0 z-50 mt-2 w-16 bg-white rounded-md shadow-lg border border-gray-200">
                            <template x-for="option in options" :key="option.value">
                                <button type="button" @click="changePerPage(option.value)"
                                    :class="perPage === option.value ? 'bg-primary/10 text-primary' : 'text-gray-700'"
                                    class="block w-full px-4 py-2 text-sm text-left hover:bg-gray-100 first:rounded-t-md last:rounded-b-md"
                                    x-text="option.label"></button>
                            </template>
                        </div>
                    </div>

                    {{-- Add Button --}}
                    <button @click="openModal = 'addFixCost'"
                        class="flex-shrink-0 flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark cursor-pointer text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Add</span>
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="mt-5 overflow-x-auto">
                <table class="min-w-[300px] w-full text-sm">
                    <thead class="bg-primary-light text-font-base">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">Category</th>
                                <th class="py-2 px-4 text-left">List Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody x-data="{
                            get filteredData() {
                                if (!this.searchFixCost.trim()) return {{ $allFixCostLists->toJson() }};
                                const search = this.searchFixCost.toLowerCase();
                                return {{ $allFixCostLists->toJson() }}.filter(item =>
                                    item.list_name.toLowerCase().includes(search) ||
                                    item.category.toLowerCase().includes(search)
                                );
                            },
                            get hasResults() {
                                return this.filteredData.length > 0;
                            },
                            getCategoryLabel(category) {
                                const labels = {
                                    'fix_cost_1': 'Fix Cost 1',
                                    'fix_cost_2': 'Fix Cost 2',
                                    'screening': 'Screening'
                                };
                                return labels[category] || category;
                            }
                        }">
                            {{-- Data ALL (untuk search) --}}
                            <template x-for="(fixCost, index) in filteredData" :key="fixCost.id">
                                <tr x-show="searchFixCost.trim() !== ''" class="border-t border-gray-200">
                                    <td class="py-2 px-4" x-text="index + 1"></td>
                                    <td class="py-2 px-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-blue-100 text-blue-800': fixCost.category === 'fix_cost_1',
                                                'bg-green-100 text-green-800': fixCost.category === 'fix_cost_2',
                                                'bg-purple-100 text-purple-800': fixCost.category === 'screening'
                                            }"
                                            x-text="getCategoryLabel(fixCost.category)"></span>
                                    </td>
                                    <td class="py-2 px-4">
                                        <div class="font-medium text-gray-900" x-text="fixCost.list_name"></div>
                                    </td>
                                    <td class="py-2 px-4 text-right">
                                        <div x-data="{
                                            open: false,
                                            dropdownStyle: {},
                                            checkPosition() {
                                                const button = this.$refs.button;
                                                const rect = button.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - rect.bottom;
                                                const spaceAbove = rect.top;
                                                const dropUp = spaceBelow < 200 && spaceAbove > spaceBelow;
                                        
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
                                            })" class="relative inline-block text-left">
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[9999]">
                                                <div class="py-1">
                                                    <button @click="editFixCost = fixCost; openModal = 'editFixCost'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>
                                                    <button type="button" @click="showDeleteFixCostConfirm = fixCost.id; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            {{-- Data Paginated --}}
                        </template>

                        @foreach ($fixCostLists as $index => $fixCost)
                            <tr x-show="searchFixCost.trim() === ''" class="border-t border-gray-200">
                                <td class="py-2 px-4">{{ $fixCostLists->firstItem() + $loop->index }}</td>
                                <td class="py-2 px-4">
                                    @php
                                        $categoryLabels = [
                                            'fix_cost_1' => ['label' => 'Fix Cost 1', 'class' => 'bg-blue-100 text-blue-800'],
                                            'fix_cost_2' => ['label' => 'Fix Cost 2', 'class' => 'bg-green-100 text-green-800'],
                                            'screening' => ['label' => 'Screening', 'class' => 'bg-purple-100 text-purple-800'],
                                        ];
                                        $category = $categoryLabels[$fixCost->category] ?? ['label' => $fixCost->category, 'class' => 'bg-gray-100 text-gray-800'];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $category['class'] }}">
                                        {{ $category['label'] }}
                                    </span>
                                </td>
                                <td class="py-2 px-4">
                                    <div class="font-medium text-gray-900">{{ $fixCost->list_name }}</div>
                                </td>
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
                                        <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                            class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                            title="Actions">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                            </svg>
                                        </button>

                                        <div x-show="open" @click.away="open = false" x-transition
                                            :style="dropdownStyle"
                                            class="rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[9999]">
                                            <div class="py-1">
                                                <button
                                                    @click="editFixCost = {{ $fixCost->toJson() }}; openModal = 'editFixCost'; open = false"
                                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Edit
                                                </button>

                                                <button type="button"
                                                    @click="showDeleteFixCostConfirm = {{ $fixCost->id }}; open = false"
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
                        @endforeach
                        
                        {{-- Empty state for search --}}
                        <tr x-show="searchFixCost.trim() !== '' && !hasResults" x-cloak>
                            <td colspan="4" class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="text-sm font-medium">No fix cost lists found</p>
                                    <p class="text-xs text-gray-400">Try searching with a different keyword</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
        <div x-show="searchFixCost.trim() === ''" id="fix-cost-pagination-container" class="mt-4">
            <x-custom-pagination :paginator="$fixCostLists" />
        </div>
    </section>
</div>

    {{-- ===================== MODALS ===================== --}}
        {{-- ADD MATERIAL SUPPLIER MODAL --}}
        <div x-show="openModal === 'addSupplier'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Material Supplier</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.finance.material-suppliers.store') }}" method="POST"
                    @submit="if (!validateAddSupplier()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Supplier Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="supplier_name" x-model="addSupplierForm.supplier_name"
                                @blur="validateAddSupplier()"
                                :class="addSupplierErrors.supplier_name ||
                                    {{ $errors->addSupplier->has('supplier_name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm pr-10 border focus:outline-none focus:ring-2 text-gray-700"
                                placeholder="e.g., PT. ABC Textile">
                            @if ($errors->addSupplier->has('supplier_name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        @if ($errors->addSupplier->has('supplier_name'))
                            <p class="mt-1 text-sm text-red-600">{{ $errors->addSupplier->first('supplier_name') }}</p>
                        @else
                            <p x-show="addSupplierErrors.supplier_name" x-text="addSupplierErrors.supplier_name"
                                class="mt-1 text-sm text-red-600"></p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" x-model="addSupplierForm.notes" rows="3"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 text-gray-700"
                            placeholder="Additional notes..."></textarea>
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

        {{-- EDIT MATERIAL SUPPLIER MODAL --}}
        <div x-show="openModal === 'editSupplier'" x-cloak x-init="@if (session('openModal') === 'editSupplier' && session('editSupplierId')) editSupplier = {{ \App\Models\MaterialSupplier::find(session('editSupplierId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Material Supplier</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/finance/material-suppliers/${editSupplier.id || ''}`" method="POST"
                    @submit="if (!validateEditSupplier()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Supplier Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="supplier_name" x-model="editSupplier.supplier_name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editSupplier->has('supplier_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('supplier_name', 'editSupplier')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" x-model="editSupplier.notes" rows="3"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 text-gray-700"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sort Order <span class="text-red-500">*</span></label>
                        <input type="number" name="sort_order" x-model.number="editSupplier.sort_order" min="1"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editSupplier->has('sort_order') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('sort_order', 'editSupplier')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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

        {{-- ADD SUPPORT PARTNER MODAL --}}
        <div x-show="openModal === 'addPartner'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Support Partner</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.finance.support-partners.store') }}" method="POST"
                    @submit="if (!validateAddPartner()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Partner Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="partner_name" x-model="addPartnerForm.partner_name"
                                @blur="validateAddPartner()"
                                :class="addPartnerErrors.partner_name ||
                                    {{ $errors->addPartner->has('partner_name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm pr-10 border focus:outline-none focus:ring-2 text-gray-700"
                                placeholder="e.g., CV. XYZ Printing">
                            @if ($errors->addPartner->has('partner_name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        @if ($errors->addPartner->has('partner_name'))
                            <p class="mt-1 text-sm text-red-600">{{ $errors->addPartner->first('partner_name') }}</p>
                        @else
                            <p x-show="addPartnerErrors.partner_name" x-text="addPartnerErrors.partner_name"
                                class="mt-1 text-sm text-red-600"></p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" x-model="addPartnerForm.notes" rows="3"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 text-gray-700"
                            placeholder="Additional notes..."></textarea>
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

        {{-- EDIT SUPPORT PARTNER MODAL --}}
        <div x-show="openModal === 'editPartner'" x-cloak x-init="@if (session('openModal') === 'editPartner' && session('editPartnerId')) editPartner = {{ \App\Models\SupportPartner::find(session('editPartnerId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Support Partner</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/finance/support-partners/${editPartner.id || ''}`" method="POST"
                    @submit="if (!validateEditPartner()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Partner Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="partner_name" x-model="editPartner.partner_name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editPartner->has('partner_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('partner_name', 'editPartner')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" x-model="editPartner.notes" rows="3"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 text-gray-700"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sort Order <span class="text-red-500">*</span></label>
                        <input type="number" name="sort_order" x-model.number="editPartner.sort_order" min="1"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editPartner->has('sort_order') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('sort_order', 'editPartner')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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

        {{-- ADD FIX COST LIST MODAL --}}
        <div x-show="openModal === 'addFixCost'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Fix Cost List</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.finance.fix-cost-lists.store') }}" method="POST"
                    @submit="if (!validateAddFixCost()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="category" x-model="addFixCostForm.category"
                            @blur="validateAddFixCost()"
                            :class="addFixCostErrors.category ||
                                {{ $errors->addFixCost->has('category') ? 'true' : 'false' }} ?
                                'w-full rounded-md px-4 py-2 text-sm border border-red-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 text-gray-700' :
                                'w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-gray-700'">
                            <option value="">Select Category</option>
                            <option value="fix_cost_1">Fix Cost 1</option>
                            <option value="fix_cost_2">Fix Cost 2</option>
                            <option value="screening">Screening</option>
                        </select>
                        @if ($errors->addFixCost->has('category'))
                            <p class="mt-1 text-sm text-red-600">{{ $errors->addFixCost->first('category') }}</p>
                        @else
                            <p x-show="addFixCostErrors.category" x-text="addFixCostErrors.category"
                                class="mt-1 text-sm text-red-600"></p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">List Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="list_name" x-model="addFixCostForm.list_name"
                                @blur="validateAddFixCost()"
                                :class="addFixCostErrors.list_name ||
                                    {{ $errors->addFixCost->has('list_name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm pr-10 border focus:outline-none focus:ring-2 text-gray-700"
                                placeholder="e.g., Electricity">
                            @if ($errors->addFixCost->has('list_name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        @if ($errors->addFixCost->has('list_name'))
                            <p class="mt-1 text-sm text-red-600">{{ $errors->addFixCost->first('list_name') }}</p>
                        @else
                            <p x-show="addFixCostErrors.list_name" x-text="addFixCostErrors.list_name"
                                class="mt-1 text-sm text-red-600"></p>
                        @endif
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

        {{-- EDIT FIX COST LIST MODAL --}}
        <div x-show="openModal === 'editFixCost'" x-cloak x-init="@if (session('openModal') === 'editFixCost' && session('editFixCostId')) editFixCost = {{ \App\Models\FixCostList::find(session('editFixCostId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Fix Cost List</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/finance/fix-cost-lists/${editFixCost.id || ''}`" method="POST"
                    @submit="if (!validateEditFixCost()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select name="category" x-model="editFixCost.category"
                            :class="{{ $errors->editFixCost->has('category') ? 'true' : 'false' }} ?
                                'w-full rounded-md px-4 py-2 text-sm border border-red-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 text-gray-700' :
                                'w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-gray-700'">
                            <option value="">Select Category</option>
                            <option value="fix_cost_1">Fix Cost 1</option>
                            <option value="fix_cost_2">Fix Cost 2</option>
                            <option value="screening">Screening</option>
                        </select>
                        @error('category', 'editFixCost')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">List Name <span class="text-red-500">*</span></label>
                        <input type="text" name="list_name" x-model="editFixCost.list_name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editFixCost->has('list_name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('list_name', 'editFixCost')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sort Order <span class="text-red-500">*</span></label>
                        <input type="number" name="sort_order" x-model.number="editFixCost.sort_order" min="1"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editFixCost->has('sort_order') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('sort_order', 'editFixCost')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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

        {{-- ================= DELETE CONFIRMATION MODALS ================= --}}
        {{-- Delete Material Supplier Modal --}}
        <div x-show="showDeleteSupplierConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity">
            <div @click.away="showDeleteSupplierConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Material Supplier?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    This action cannot be undone. This will permanently delete the supplier data.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteSupplierConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form :action="`/owner/manage-data/finance/material-suppliers/${showDeleteSupplierConfirm}`" method="POST" class="flex-1">
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

        {{-- Delete Support Partner Modal --}}
        <div x-show="showDeletePartnerConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity">
            <div @click.away="showDeletePartnerConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Support Partner?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    This action cannot be undone. This will permanently delete the partner data.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeletePartnerConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form :action="`/owner/manage-data/finance/support-partners/${showDeletePartnerConfirm}`" method="POST" class="flex-1">
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

        {{-- Delete Fix Cost List Modal --}}
        <div x-show="showDeleteFixCostConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity">
            <div @click.away="showDeleteFixCostConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Fix Cost List?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    This action cannot be undone. This will permanently delete the fix cost list data.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteFixCostConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form :action="`/owner/manage-data/finance/fix-cost-lists/${showDeleteFixCostConfirm}`" method="POST" class="flex-1">
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

        {{-- AJAX Pagination Script --}}
        <script>
            function setupAllPagination() {
                setupPagination('supplier-pagination-container', 'material-suppliers');
                setupPagination('partner-pagination-container', 'support-partners');
                setupPagination('fix-cost-pagination-container', 'fix-cost-lists');
            }

            function setupPagination(containerId, sectionId) {
                const container = document.getElementById(containerId);
                if (!container) return;

                const oldListener = container._paginationListener;
                if (oldListener) {
                    container.removeEventListener('click', oldListener);
                }

                const listener = function(e) {
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
                            const newSection = doc.getElementById(sectionId);
                            const currentSection = document.getElementById(sectionId);
                            
                            if (newSection && currentSection) {
                                currentSection.innerHTML = newSection.innerHTML;
                            }

                            if (currentSection) {
                                setTimeout(() => {
                                    currentSection.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'start'
                                    });
                                }, 100);
                            }

                            setupPagination(containerId, sectionId);
                        })
                        .catch(error => {
                            console.error('Error loading pagination:', error);
                        });
                };

                container.addEventListener('click', listener);
                container._paginationListener = listener;
            }

            document.addEventListener('DOMContentLoaded', function() {
                setupAllPagination();
            });

            document.addEventListener('turbo:load', function() {
                setupAllPagination();
            });

            document.addEventListener('turbo:render', function() {
                setupAllPagination();
            });
        </script>
    </div>
@endsection
