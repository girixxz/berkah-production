@extends('layouts.app')
@section('title', 'Manage Work Order Data')
@section('content')

    <x-nav-locate :items="['Menu', 'Manage Data', 'Work Order Data']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        // Modal State
        openModal: '{{ session('openModal') }}'
        @if ($errors->addCuttingPattern->any()) || 'addCuttingPattern' @endif
        @if ($errors->addChainCloth->any()) || 'addChainCloth' @endif
        @if ($errors->addRibSize->any()) || 'addRibSize' @endif
        @if ($errors->addPrintInk->any()) || 'addPrintInk' @endif
        @if ($errors->addFinishing->any()) || 'addFinishing' @endif
        @if ($errors->addNeckOverdeck->any()) || 'addNeckOverdeck' @endif
        @if ($errors->editCuttingPattern->any()) || 'editCuttingPattern' @endif
        @if ($errors->editChainCloth->any()) || 'editChainCloth' @endif
        @if ($errors->editRibSize->any()) || 'editRibSize' @endif
        @if ($errors->editPrintInk->any()) || 'editPrintInk' @endif
        @if ($errors->editFinishing->any()) || 'editFinishing' @endif
        @if ($errors->editNeckOverdeck->any()) || 'editNeckOverdeck' @endif
        @if ($errors->addUnderarmOverdeck->any()) || 'addUnderarmOverdeck' @endif
        @if ($errors->addSideSplit->any()) || 'addSideSplit' @endif
        @if ($errors->addSewingLabel->any()) || 'addSewingLabel' @endif
        @if ($errors->addPlasticPacking->any()) || 'addPlasticPacking' @endif
        @if ($errors->addSticker->any()) || 'addSticker' @endif
        @if ($errors->editUnderarmOverdeck->any()) || 'editUnderarmOverdeck' @endif
        @if ($errors->editSideSplit->any()) || 'editSideSplit' @endif
        @if ($errors->editSewingLabel->any()) || 'editSewingLabel' @endif
        @if ($errors->editPlasticPacking->any()) || 'editPlasticPacking' @endif
        @if ($errors->editSticker->any()) || 'editSticker' @endif,
    
        // Search States
        searchCuttingPattern: '',
        searchChainCloth: '',
        searchRibSize: '',
        searchPrintInk: '',
        searchFinishing: '',
        searchNeckOverdeck: '',
        searchUnderarmOverdeck: '',
        searchSideSplit: '',
        searchSewingLabel: '',
        searchPlasticPacking: '',
        searchSticker: '',
    
        // Edit Data States
        editCuttingPattern: {},
        editChainCloth: {},
        editRibSize: {},
        editPrintInk: {},
        editFinishing: {},
        editNeckOverdeck: {},
        editUnderarmOverdeck: {},
        editSideSplit: {},
        editSewingLabel: {},
        editPlasticPacking: {},
        editSticker: {},
    
        // Delete Confirmation States
        showDeleteCuttingPatternConfirm: null,
        showDeleteChainClothConfirm: null,
        showDeleteRibSizeConfirm: null,
        showDeletePrintInkConfirm: null,
        showDeleteFinishingConfirm: null,
        showDeleteNeckOverdeckConfirm: null,
        showDeleteUnderarmOverdeckConfirm: null,
        showDeleteSideSplitConfirm: null,
        showDeleteSewingLabelConfirm: null,
        showDeletePlasticPackingConfirm: null,
        showDeleteStickerConfirm: null,
    
        // Add Form States
        addCuttingPatternForm: { name: '{{ old('name') }}' },
        addCuttingPatternErrors: {},
        addChainClothForm: { name: '{{ old('name') }}' },
        addChainClothErrors: {},
        addRibSizeForm: { name: '{{ old('name') }}' },
        addRibSizeErrors: {},
        addPrintInkForm: { name: '{{ old('name') }}' },
        addPrintInkErrors: {},
        addFinishingForm: { name: '{{ old('name') }}' },
        addFinishingErrors: {},
        addNeckOverdeckForm: { name: '{{ old('name') }}' },
        addNeckOverdeckErrors: {},
        addUnderarmOverdeckForm: { name: '{{ old('name') }}' },
        addUnderarmOverdeckErrors: {},
        addSideSplitForm: { name: '{{ old('name') }}' },
        addSideSplitErrors: {},
        addSewingLabelForm: { name: '{{ old('name') }}' },
        addSewingLabelErrors: {},
        addPlasticPackingForm: { name: '{{ old('name') }}' },
        addPlasticPackingErrors: {},
        addStickerForm: { name: '{{ old('name') }}' },
        addStickerErrors: {},
    
        // ==================== Validation Functions ====================
        validateAddCuttingPattern() {
            this.addCuttingPatternErrors = {};
            if (!this.addCuttingPatternForm.name) {
                this.addCuttingPatternErrors.name = 'Cutting Pattern name is required';
            } else if (this.addCuttingPatternForm.name.length > 100) {
                this.addCuttingPatternErrors.name = 'Cutting Pattern name must not exceed 100 characters';
            }
            return Object.keys(this.addCuttingPatternErrors).length === 0;
        },
    
        validateAddChainCloth() {
            this.addChainClothErrors = {};
            if (!this.addChainClothForm.name) {
                this.addChainClothErrors.name = 'Chain Cloth name is required';
            } else if (this.addChainClothForm.name.length > 100) {
                this.addChainClothErrors.name = 'Chain Cloth name must not exceed 100 characters';
            }
            return Object.keys(this.addChainClothErrors).length === 0;
        },
    
        validateAddRibSize() {
            this.addRibSizeErrors = {};
            if (!this.addRibSizeForm.name) {
                this.addRibSizeErrors.name = 'Rib Size name is required';
            } else if (this.addRibSizeForm.name.length > 100) {
                this.addRibSizeErrors.name = 'Rib Size name must not exceed 100 characters';
            }
            return Object.keys(this.addRibSizeErrors).length === 0;
        },
    
        validateAddPrintInk() {
            this.addPrintInkErrors = {};
            if (!this.addPrintInkForm.name) {
                this.addPrintInkErrors.name = 'Print Ink name is required';
            } else if (this.addPrintInkForm.name.length > 100) {
                this.addPrintInkErrors.name = 'Print Ink name must not exceed 100 characters';
            }
            return Object.keys(this.addPrintInkErrors).length === 0;
        },
    
        validateAddFinishing() {
            this.addFinishingErrors = {};
            if (!this.addFinishingForm.name) {
                this.addFinishingErrors.name = 'Finishing name is required';
            } else if (this.addFinishingForm.name.length > 100) {
                this.addFinishingErrors.name = 'Finishing name must not exceed 100 characters';
            }
            return Object.keys(this.addFinishingErrors).length === 0;
        },
    
        validateAddNeckOverdeck() {
            this.addNeckOverdeckErrors = {};
            if (!this.addNeckOverdeckForm.name) {
                this.addNeckOverdeckErrors.name = 'NeckOverdeck name is required';
            } else if (this.addNeckOverdeckForm.name.length > 100) {
                this.addNeckOverdeckErrors.name = 'NeckOverdeck name must not exceed 100 characters';
            }
            return Object.keys(this.addNeckOverdeckErrors).length === 0;
        },
    
        validateAddUnderarmOverdeck() {
            this.addUnderarmOverdeckErrors = {};
            if (!this.addUnderarmOverdeckForm.name) {
                this.addUnderarmOverdeckErrors.name = 'Underarm Overdeck name is required';
            } else if (this.addUnderarmOverdeckForm.name.length > 100) {
                this.addUnderarmOverdeckErrors.name = 'Underarm Overdeck name must not exceed 100 characters';
            }
            return Object.keys(this.addUnderarmOverdeckErrors).length === 0;
        },
    
        validateAddSideSplit() {
            this.addSideSplitErrors = {};
            if (!this.addSideSplitForm.name) {
                this.addSideSplitErrors.name = 'Side Split name is required';
            } else if (this.addSideSplitForm.name.length > 100) {
                this.addSideSplitErrors.name = 'Side Split name must not exceed 100 characters';
            }
            return Object.keys(this.addSideSplitErrors).length === 0;
        },
    
        validateAddSewingLabel() {
            this.addSewingLabelErrors = {};
            if (!this.addSewingLabelForm.name) {
                this.addSewingLabelErrors.name = 'Sewing Label name is required';
            } else if (this.addSewingLabelForm.name.length > 100) {
                this.addSewingLabelErrors.name = 'Sewing Label name must not exceed 100 characters';
            }
            return Object.keys(this.addSewingLabelErrors).length === 0;
        },
    
        validateAddPlasticPacking() {
            this.addPlasticPackingErrors = {};
            if (!this.addPlasticPackingForm.name) {
                this.addPlasticPackingErrors.name = 'Plastic Packing name is required';
            } else if (this.addPlasticPackingForm.name.length > 100) {
                this.addPlasticPackingErrors.name = 'Plastic Packing name must not exceed 100 characters';
            }
            return Object.keys(this.addPlasticPackingErrors).length === 0;
        },
    
        validateAddSticker() {
            this.addStickerErrors = {};
            if (!this.addStickerForm.name) {
                this.addStickerErrors.name = 'Sticker name is required';
            } else if (this.addStickerForm.name.length > 100) {
                this.addStickerErrors.name = 'Sticker name must not exceed 100 characters';
            }
            return Object.keys(this.addStickerErrors).length === 0;
        },
    
        // ==================== Initialization ====================
        init() {
            // Auto scroll to section after Add/Edit/Delete operations
            const scrollTarget = '{{ session('scrollToSection') }}';
            if (scrollTarget) {
                setTimeout(() => {
                    const section = document.getElementById(scrollTarget);
                    if (section) {
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100);
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
    
                // Reset forms when modals open (only if no errors)
                @if(!$errors->addCuttingPattern->any())
                if (value === 'addCuttingPattern') {
                    this.addCuttingPatternForm = { name: '' };
                    this.addCuttingPatternErrors = {};
                }
                @endif
                @if(!$errors->addChainCloth->any())
                if (value === 'addChainCloth') {
                    this.addChainClothForm = { name: '' };
                    this.addChainClothErrors = {};
                }
                @endif
                @if(!$errors->addRibSize->any())
                if (value === 'addRibSize') {
                    this.addRibSizeForm = { name: '' };
                    this.addRibSizeErrors = {};
                }
                @endif
                @if(!$errors->addPrintInk->any())
                if (value === 'addPrintInk') {
                    this.addPrintInkForm = { name: '' };
                    this.addPrintInkErrors = {};
                }
                @endif
                @if(!$errors->addFinishing->any())
                if (value === 'addFinishing') {
                    this.addFinishingForm = { name: '' };
                    this.addFinishingErrors = {};
                }
                @endif
                @if(!$errors->addNeckOverdeck->any())
                if (value === 'addNeckOverdeck') {
                    this.addNeckOverdeckForm = { name: '' };
                    this.addNeckOverdeckErrors = {};
                }
                @endif
                @if(!$errors->addUnderarmOverdeck->any())
                if (value === 'addUnderarmOverdeck') {
                    this.addUnderarmOverdeckForm = { name: '' };
                    this.addUnderarmOverdeckErrors = {};
                }
                @endif
                @if(!$errors->addSideSplit->any())
                if (value === 'addSideSplit') {
                    this.addSideSplitForm = { name: '' };
                    this.addSideSplitErrors = {};
                }
                @endif
                @if(!$errors->addSewingLabel->any())
                if (value === 'addSewingLabel') {
                    this.addSewingLabelForm = { name: '' };
                    this.addSewingLabelErrors = {};
                }
                @endif
                @if(!$errors->addPlasticPacking->any())
                if (value === 'addPlasticPacking') {
                    this.addPlasticPackingForm = { name: '' };
                    this.addPlasticPackingErrors = {};
                }
                @endif
                @if(!$errors->addSticker->any())
                if (value === 'addSticker') {
                    this.addStickerForm = { name: '' };
                    this.addStickerErrors = {};
                }
                @endif
            });
        }
    }" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

        {{-- ===================== Cutting Patterns ===================== --}}
        <section id="cutting-patterns" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                {{-- Judul --}}
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Cutting Patterns
                </h2>

                {{-- Spacer biar search bisa fleksibel --}}
                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchCuttingPattern" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addCuttingPattern'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>

            {{-- Table Product Category --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">Product Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cuttingPatterns as $cuttingPattern)
                                <tr class="border-t border-gray-200"
                                    x-show="searchCuttingPattern.length < 1 || '{{ strtolower($cuttingPattern->name) }}'.includes(searchCuttingPattern.toLowerCase())">
                                    <td class="py-2 px-4">{{ $cuttingPatterns->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $cuttingPattern->name }}</td>
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
                                                        @click="editCuttingPattern = {{ $cuttingPattern->toJson() }}; openModal = 'editCuttingPattern'; open = false"
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
                                                        @click="showDeleteCuttingPatternConfirm = {{ $cuttingPattern->id }}; open = false"
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
                                    <td colspan="3" class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Cutting Patterns founds.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="cutting-pattern-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$cuttingPatterns" />
                </div>
            </div>
        </section>

        {{-- ===================== Chain Cloths ===================== --}}
        <section id="chain-cloths" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                {{-- Judul --}}
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Chain Cloths
                </h2>

                {{-- Spacer biar search bisa fleksibel --}}
                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchChainCloth" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addChainCloth'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>

            {{-- Table Material Category --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-sm">No</th>
                                <th class="py-2 px-4 text-left">Chain Cloth Name</th>
                                <th class="py-2 px-4 text-right rounded-r-sm">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($chainCloths as $chainCloth)
                                <tr class="border-t border-gray-200"
                                    x-show="searchChainCloth.length < 1 || '{{ strtolower($chainCloth->name) }}'.includes(searchChainCloth.toLowerCase())">
                                    <td class="py-2 px-4">{{ $chainCloths->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $chainCloth->name }}</td>
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
                                                        @click="editChainCloth = {{ $chainCloth->toJson() }}; openModal = 'editChainCloth'; open = false"
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
                                                        @click="showDeleteChainClothConfirm = {{ $chainCloth->id }}; open = false"
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
                                    <td colspan="3"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Chain Cloths founds.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="chain-cloth-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$chainCloths" />
                </div>
            </div>
        </section>

        {{-- ===================== Rib Sizes ===================== --}}
        <section id="rib-sizes" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                {{-- Judul --}}
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Rib Sizes
                </h2>

                {{-- Spacer biar search bisa fleksibel --}}
                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchRibSize" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addRibSize'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>

            {{-- Table Material Texture --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">Rib Finishing Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ribSizes as $ribSize)
                                <tr class="border-t border-gray-200"
                                    x-show="searchRibSize.length < 1 || '{{ strtolower($ribSize->name) }}'.includes(searchRibSize.toLowerCase())">
                                    <td class="py-2 px-4">{{ $ribSizes->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $ribSize->name }}</td>
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
                                                        @click="editRibSize = {{ $ribSize->toJson() }}; openModal = 'editRibSize'; open = false"
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
                                                        @click="showDeleteRibSizeConfirm = {{ $ribSize->id }}; open = false"
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
                                    <td colspan="3"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Rib Sizes founds.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <!-- Pagination -->
                <div id="rib-size-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$ribSizes" />
                </div>
            </div>
        </section>

        {{-- ===================== Print Inks ===================== --}}
        <section id="print-inks" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Print Inks
                </h2>

                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchPrintInk" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                    focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addPrintInk'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">Print Ink Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($printInks as $printInk)
                                <tr class="border-t border-gray-200"
                                    x-show="searchPrintInk.length < 1 || '{{ strtolower($printInk->name) }}'.includes(searchPrintInk.toLowerCase())">
                                    <td class="py-2 px-4">{{ $printInks->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $printInk->name }}</td>
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
                                                        @click="editPrintInk = {{ $printInk->toJson() }}; openModal = 'editPrintInk'; open = false"
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
                                                        @click="showDeletePrintInkConfirm = {{ $printInk->id }}; open = false"
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
                                    <td colspan="3"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Print Inks founds.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="print-ink-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$printInks" />
                </div>
            </div>
        </section>

        {{-- ===================== Finishings ===================== --}}
        <section id="finishings" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                {{-- Judul --}}
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Finishings
                </h2>

                {{-- Spacer biar search bisa fleksibel --}}
                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchFinishing" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addFinishing'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>


            {{-- Table Material Size --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">Finishing Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($finishings as $finishing)
                                <tr class="border-t border-gray-200"
                                    x-show="searchFinishing.length < 1 || '{{ strtolower($finishing->name) }}'.includes(searchFinishing.toLowerCase())">
                                    <td class="py-2 px-4">{{ $finishings->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $finishing->name }}</td>
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
                                                        @click="editFinishing = {{ $finishing->toJson() }}; openModal = 'editFinishing'; open = false"
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
                                                        @click="showDeleteFinishingConfirm = {{ $finishing->id }}; open = false"
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
                                        No Finishings founds.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <!-- Pagination -->
                <div id="finishing-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$finishings" />
                </div>
            </div>
        </section>

        {{-- ===================== NeckOverdeck ===================== --}}
        <section id="neck-overdecks" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                {{-- Judul --}}
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Neck Overdecks
                </h2>

                {{-- Spacer biar search bisa fleksibel --}}
                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchNeckOverdeck" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addNeckOverdeck'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>


            {{-- Table NeckOverdecks --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">NeckOverdeck Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($neckOverdecks as $neckOverdeck)
                                <tr class="border-t border-gray-200"
                                    x-show="searchNeckOverdeck.length < 1 || '{{ strtolower($neckOverdeck->name) }}'.includes(searchNeckOverdeck.toLowerCase())">
                                    <td class="py-2 px-4">{{ $neckOverdecks->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $neckOverdeck->name }}</td>
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
                                                        @click="editNeckOverdeck = {{ $neckOverdeck->toJson() }}; openModal = 'editNeckOverdeck'; open = false"
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
                                                        @click="showDeleteNeckOverdeckConfirm = {{ $neckOverdeck->id }}; open = false"
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
                                    <td colspan="3"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No NeckOverdecks found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <!-- Pagination -->
                <div id="neck-overdeck-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$neckOverdecks" />
                </div>
            </div>
        </section>

        <section id="underarm-overdecks" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                {{-- Judul --}}
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Underarm Overdecks
                </h2>

                {{-- Spacer biar search bisa fleksibel --}}
                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchUnderarmOverdeck" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addUnderarmOverdeck'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>


            {{-- Table UnderarmOverdecks --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">UnderarmOverdeck Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($underarmOverdecks as $underarmOverdeck)
                                <tr class="border-t border-gray-200"
                                    x-show="searchUnderarmOverdeck.length < 1 || '{{ strtolower($underarmOverdeck->name) }}'.includes(searchUnderarmOverdeck.toLowerCase())">
                                    <td class="py-2 px-4">{{ $underarmOverdecks->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $underarmOverdeck->name }}</td>
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
                                                        @click="editUnderarmOverdeck = {{ $underarmOverdeck->toJson() }}; openModal = 'editUnderarmOverdeck'; open = false"
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
                                                        @click="showDeleteUnderarmOverdeckConfirm = {{ $underarmOverdeck->id }}; open = false"
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
                                    <td colspan="3"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No UnderarmOverdecks found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <!-- Pagination -->
                <div id="underarm-overdeck-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$underarmOverdecks" />
                </div>
            </div>
        </section>

        <section id="side-splits" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                {{-- Judul --}}
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Side Splits
                </h2>

                {{-- Spacer biar search bisa fleksibel --}}
                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchSideSplit" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addSideSplit'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>


            {{-- Table SideSplits --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">SideSplit Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sideSplits as $sideSplit)
                                <tr class="border-t border-gray-200"
                                    x-show="searchSideSplit.length < 1 || '{{ strtolower($sideSplit->name) }}'.includes(searchSideSplit.toLowerCase())">
                                    <td class="py-2 px-4">{{ $sideSplits->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $sideSplit->name }}</td>
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
                                                        @click="editSideSplit = {{ $sideSplit->toJson() }}; openModal = 'editSideSplit'; open = false"
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
                                                        @click="showDeleteSideSplitConfirm = {{ $sideSplit->id }}; open = false"
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
                                    <td colspan="3"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No SideSplits found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <!-- Pagination -->
                <div id="side-split-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$sideSplits" />
                </div>
            </div>
        </section>

        <section id="sewing-labels" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                {{-- Judul --}}
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Sewing Labels
                </h2>

                {{-- Spacer biar search bisa fleksibel --}}
                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchSewingLabel" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addSewingLabel'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>


            {{-- Table SewingLabels --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">SewingLabel Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sewingLabels as $sewingLabel)
                                <tr class="border-t border-gray-200"
                                    x-show="searchSewingLabel.length < 1 || '{{ strtolower($sewingLabel->name) }}'.includes(searchSewingLabel.toLowerCase())">
                                    <td class="py-2 px-4">{{ $sewingLabels->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $sewingLabel->name }}</td>
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
                                                        @click="editSewingLabel = {{ $sewingLabel->toJson() }}; openModal = 'editSewingLabel'; open = false"
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
                                                        @click="showDeleteSewingLabelConfirm = {{ $sewingLabel->id }}; open = false"
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
                                    <td colspan="3"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No SewingLabels found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <!-- Pagination -->
                <div id="sewing-label-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$sewingLabels" />
                </div>
            </div>
        </section>

        <section id="plastic-packings" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                {{-- Judul --}}
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Plastic Packings
                </h2>

                {{-- Spacer biar search bisa fleksibel --}}
                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchPlasticPacking" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addPlasticPacking'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>


            {{-- Table PlasticPackings --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">PlasticPacking Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($plasticPackings as $plasticPacking)
                                <tr class="border-t border-gray-200"
                                    x-show="searchPlasticPacking.length < 1 || '{{ strtolower($plasticPacking->name) }}'.includes(searchPlasticPacking.toLowerCase())">
                                    <td class="py-2 px-4">{{ $plasticPackings->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $plasticPacking->name }}</td>
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
                                                        @click="editPlasticPacking = {{ $plasticPacking->toJson() }}; openModal = 'editPlasticPacking'; open = false"
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
                                                        @click="showDeletePlasticPackingConfirm = {{ $plasticPacking->id }}; open = false"
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
                                    <td colspan="3"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No PlasticPackings found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <!-- Pagination -->
                <div id="plastic-packing-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$plasticPackings" />
                </div>
            </div>
        </section>

        <section id="stickers" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                {{-- Judul --}}
                <h2 class="text-xl font-semibold text-gray-900 flex-shrink-0">
                    Stickers
                </h2>

                {{-- Spacer biar search bisa fleksibel --}}
                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="relative flex-1 min-w-[100px]">
                        <x-icons.search />
                        <input type="text" x-model="searchSticker" placeholder="Search Items"
                            class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>

                    {{-- Add Items --}}
                    <button @click="openModal = 'addSticker'"
                        class="cursor-pointer flex-shrink-0 w-18 whitespace-nowrap px-3 py-2 rounded-md
                   bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add
                    </button>
                </div>
            </div>


            {{-- Table Stickers --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-124 overflow-y-auto">
                    <table class="min-w-[300px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">Sticker Name</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stickers as $sticker)
                                <tr class="border-t border-gray-200"
                                    x-show="searchSticker.length < 1 || '{{ strtolower($sticker->name) }}'.includes(searchSticker.toLowerCase())">
                                    <td class="py-2 px-4">{{ $stickers->firstItem() + $loop->index }}</td>
                                    <td class="py-2 px-4">{{ $sticker->name }}</td>
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
                                                        @click="editSticker = {{ $sticker->toJson() }}; openModal = 'editSticker'; open = false"
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
                                                        @click="showDeleteStickerConfirm = {{ $sticker->id }}; open = false"
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
                                    <td colspan="3"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Stickers found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <!-- Pagination -->
                <div id="sticker-pagination-container" class="mt-4">
                    <x-custom-pagination :paginator="$stickers" />
                </div>
            </div>
        </section>


        {{-- ===================== MODALS ===================== --}}
        {{-- ========== Add & Edit Cutting Pattern Modal ========== --}}
        <div x-show="openModal === 'addCuttingPattern'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Cutting Pattern</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.cutting-patterns.store') }}" method="POST"
                    @submit="if (!validateAddCuttingPattern()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cutting Pattern Name</label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addCuttingPatternForm.name"
                                @blur="validateAddCuttingPattern()"
                                :class="addCuttingPatternErrors.name ||
                                    {{ $errors->addCuttingPattern->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addCuttingPattern->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addCuttingPatternErrors.name" x-text="addCuttingPatternErrors.name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('name', 'addCuttingPattern')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editCuttingPattern'" x-cloak x-init="@if (session('openModal') === 'editCuttingPattern' && session('editCuttingPatternId')) editCuttingPattern = {{ \App\Models\CuttingPattern::find(session('editCuttingPatternId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Cutting Pattern</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/cutting-patterns/${editCuttingPattern.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cutting Pattern Name</label>
                        <input type="text" name="name" x-model="editCuttingPattern.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editCuttingPattern->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editCuttingPattern')
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

        {{-- ========== Add & Edit Chain Cloth Modal ========== --}}
        <div x-show="openModal === 'addChainCloth'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Chain Cloth</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.chain-cloths.store') . '#chain-cloths' }}"
                    method="POST" @submit="if (!validateAddChainCloth()) $event.preventDefault()"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Chain Cloth Name</label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addChainClothForm.name"
                                @blur="validateAddChainCloth()"
                                :class="addChainClothErrors.name ||
                                    {{ $errors->addChainCloth->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addChainCloth->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">

                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addChainClothErrors.name" x-text="addChainClothErrors.name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('name', 'addChainCloth')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editChainCloth'" x-cloak x-init="@if (session('openModal') === 'editChainCloth' && session('editChainClothId')) editChainCloth = {{ \App\Models\ChainCloth::find(session('editChainClothId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Chain Cloth</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/chain-cloths/${editChainCloth.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Chain Cloth Name</label>
                        <input type="text" name="name" x-model="editChainCloth.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editChainCloth->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editChainCloth')
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

        {{-- ========== Add & Edit Material Texture Modal ========== --}}
        <div x-show="openModal === 'addRibSize'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Material Texture</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.rib-sizes.store') }}" method="POST"
                    @submit="if (!validateAddRibSize()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rib Finishing Name</label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addRibSizeForm.name"
                                @blur="validateAddRibSize()"
                                :class="addRibSizeErrors.name ||
                                    {{ $errors->addRibSize->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addRibSize->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addRibSizeErrors.name" x-text="addRibSizeErrors.name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('name', 'addRibSize')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editRibSize'" x-cloak x-init="@if (session('openModal') === 'editRibSize' && session('editRibSizeId')) editRibSize = {{ \App\Models\RibSize::find(session('editRibSizeId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Material Texture</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/rib-sizes/${editRibSize.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rib Finishing Name</label>
                        <input type="text" name="name" x-model="editRibSize.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editRibSize->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editRibSize')
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

        {{-- ========== Add & Edit Material Sleeve Modal ========== --}}
        <div x-show="openModal === 'addPrintInk'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Material Sleeve</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.print-inks.store') }}" method="POST"
                    @submit="if (!validateAddPrintInk()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Print Ink Name</label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addPrintInkForm.name"
                                @blur="validateAddPrintInk()"
                                :class="addPrintInkErrors.name ||
                                    {{ $errors->addPrintInk->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addPrintInk->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addPrintInkErrors.name" x-text="addPrintInkErrors.name"
                            class="mt-1 text-sm text-red-600">
                        </p>
                        @error('name', 'addPrintInk')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editPrintInk'" x-cloak x-init="@if (session('openModal') === 'editPrintInk' && session('editPrintInkId')) editPrintInk = {{ \App\Models\PrintInk::find(session('editPrintInkId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Material Sleeve</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/print-inks/${editPrintInk.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Print Ink Name</label>
                        <input type="text" name="name" x-model="editPrintInk.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editPrintInk->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editPrintInk')
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

        {{-- ========== Add & Edit Material Size Modal ========== --}}
        <div x-show="openModal === 'addFinishing'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Material Size</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.finishings.store') }}" method="POST"
                    @submit="if (!validateAddFinishing()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Finishing Name <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addFinishingForm.name"
                                @blur="validateAddFinishing()"
                                :class="addFinishingErrors.name ||
                                    {{ $errors->addFinishing->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addFinishing->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addFinishingErrors.name" x-text="addFinishingErrors.name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('name', 'addFinishing')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editFinishing'" x-cloak x-init="@if (session('openModal') === 'editFinishing' && session('editFinishingId')) editFinishing = {{ \App\Models\Finishing::find(session('editFinishingId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Material Size</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/finishings/${editFinishing.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Finishing Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="name" x-model="editFinishing.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editFinishing->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editFinishing')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Extra Price <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">Rp</span>
                            <input type="number" name="extra_price" x-model="editFinishing.extra_price" step="0.01"
                                min="0"
                                class="mt-1 w-full rounded-md pl-12 pr-4 py-2 text-sm border {{ $errors->editFinishing->has('extra_price') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->editFinishing->has('extra_price'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        @error('extra_price', 'editFinishing')
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

        {{-- ========== Add & Edit NeckOverdeck Modal ========== --}}
        <div x-show="openModal === 'addNeckOverdeck'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add NeckOverdeck</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.neck-overdecks.store') }}" method="POST"
                    @submit="if (!validateAddNeckOverdeck()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">NeckOverdeck Name</label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addNeckOverdeckForm.name"
                                @blur="validateAddNeckOverdeck()"
                                :class="addNeckOverdeckErrors.name ||
                                    {{ $errors->addNeckOverdeck->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addNeckOverdeck->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addNeckOverdeckErrors.name" x-text="addNeckOverdeckErrors.name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('name', 'addNeckOverdeck')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editNeckOverdeck'" x-cloak x-init="@if (session('openModal') === 'editNeckOverdeck' && session('editNeckOverdeckId')) editNeckOverdeck = {{ \App\Models\NeckOverdeck::find(session('editNeckOverdeckId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit NeckOverdeck</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/neck-overdecks/${editNeckOverdeck.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">NeckOverdeck Name</label>
                        <input type="text" name="name" x-model="editNeckOverdeck.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editNeckOverdeck->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editNeckOverdeck')
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

        {{-- Underarm Overdeck Add Modal --}}
        <div x-show="openModal === 'addUnderarmOverdeck'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add UnderarmOverdeck</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.underarm-overdecks.store') }}" method="POST"
                    @submit="if (!validateAddUnderarmOverdeck()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">UnderarmOverdeck Name</label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addUnderarmOverdeckForm.name"
                                @blur="validateAddUnderarmOverdeck()"
                                :class="addUnderarmOverdeckErrors.name ||
                                    {{ $errors->addUnderarmOverdeck->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addUnderarmOverdeck->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addUnderarmOverdeckErrors.name" x-text="addUnderarmOverdeckErrors.name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('name', 'addUnderarmOverdeck')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editUnderarmOverdeck'" x-cloak x-init="@if (session('openModal') === 'editUnderarmOverdeck' && session('editUnderarmOverdeckId')) editUnderarmOverdeck = {{ \App\Models\UnderarmOverdeck::find(session('editUnderarmOverdeckId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit UnderarmOverdeck</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/underarm-overdecks/${editUnderarmOverdeck.id}`"
                    method="POST" class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">UnderarmOverdeck Name</label>
                        <input type="text" name="name" x-model="editUnderarmOverdeck.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editUnderarmOverdeck->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editUnderarmOverdeck')
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

        {{-- Side Split Add Modal --}}
        <div x-show="openModal === 'addSideSplit'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add SideSplit</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.side-splits.store') }}" method="POST"
                    @submit="if (!validateAddSideSplit()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SideSplit Name</label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addSideSplitForm.name"
                                @blur="validateAddSideSplit()"
                                :class="addSideSplitErrors.name ||
                                    {{ $errors->addSideSplit->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addSideSplit->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addSideSplitErrors.name" x-text="addSideSplitErrors.name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('name', 'addSideSplit')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editSideSplit'" x-cloak x-init="@if (session('openModal') === 'editSideSplit' && session('editSideSplitId')) editSideSplit = {{ \App\Models\SideSplit::find(session('editSideSplitId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit SideSplit</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/side-splits/${editSideSplit.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">SideSplit Name</label>
                        <input type="text" name="name" x-model="editSideSplit.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editSideSplit->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editSideSplit')
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

        {{-- Sewing Label Add Modal --}}
        <div x-show="openModal === 'addSewingLabel'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add SewingLabel</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.sewing-labels.store') }}" method="POST"
                    @submit="if (!validateAddSewingLabel()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">SewingLabel Name</label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addSewingLabelForm.name"
                                @blur="validateAddSewingLabel()"
                                :class="addSewingLabelErrors.name ||
                                    {{ $errors->addSewingLabel->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addSewingLabel->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addSewingLabelErrors.name" x-text="addSewingLabelErrors.name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('name', 'addSewingLabel')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editSewingLabel'" x-cloak x-init="@if (session('openModal') === 'editSewingLabel' && session('editSewingLabelId')) editSewingLabel = {{ \App\Models\SewingLabel::find(session('editSewingLabelId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit SewingLabel</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/sewing-labels/${editSewingLabel.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">SewingLabel Name</label>
                        <input type="text" name="name" x-model="editSewingLabel.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editSewingLabel->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editSewingLabel')
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

        {{-- Plastic Packing Add Modal --}}
        <div x-show="openModal === 'addPlasticPacking'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add PlasticPacking</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.plastic-packings.store') }}" method="POST"
                    @submit="if (!validateAddPlasticPacking()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">PlasticPacking Name</label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addPlasticPackingForm.name"
                                @blur="validateAddPlasticPacking()"
                                :class="addPlasticPackingErrors.name ||
                                    {{ $errors->addPlasticPacking->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addPlasticPacking->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addPlasticPackingErrors.name" x-text="addPlasticPackingErrors.name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('name', 'addPlasticPacking')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editPlasticPacking'" x-cloak x-init="@if (session('openModal') === 'editPlasticPacking' && session('editPlasticPackingId')) editPlasticPacking = {{ \App\Models\PlasticPacking::find(session('editPlasticPackingId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit PlasticPacking</h3>
                    <button @click.away="openModal=null"
                        class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/plastic-packings/${editPlasticPacking.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">PlasticPacking Name</label>
                        <input type="text" name="name" x-model="editPlasticPacking.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editPlasticPacking->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editPlasticPacking')
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

        {{-- Sticker Add Modal --}}
        <div x-show="openModal === 'addSticker'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Sticker</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form action="{{ route('owner.manage-data.work-orders.stickers.store') }}" method="POST"
                    @submit="if (!validateAddSticker()) $event.preventDefault()" class="px-6 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sticker Name</label>
                        <div class="relative">
                            <input type="text" name="name" x-model="addStickerForm.name"
                                @blur="validateAddSticker()"
                                :class="addStickerErrors.name ||
                                    {{ $errors->addSticker->has('name') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->addSticker->has('name'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addStickerErrors.name" x-text="addStickerErrors.name"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('name', 'addSticker')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
        <div x-show="openModal === 'editSticker'" x-cloak x-init="@if (session('openModal') === 'editSticker' && session('editStickerId')) editSticker = {{ \App\Models\Sticker::find(session('editStickerId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Sticker</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`/owner/manage-data/work-orders/stickers/${editSticker.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sticker Name</label>
                        <input type="text" name="name" x-model="editSticker.name"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->editSticker->has('name') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('name', 'editSticker')
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
        {{-- Delete Cutting Pattern Modal --}}
        <div x-show="showDeleteCuttingPatternConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeleteCuttingPatternConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Cutting Pattern?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this product category? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteCuttingPatternConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/cutting-patterns/' +
                        showDeleteCuttingPatternConfirm"
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

        {{-- Delete Chain Cloth Modal --}}
        <div x-show="showDeleteChainClothConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeleteChainClothConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Chain Cloth?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this material category? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteChainClothConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/chain-cloths/' +
                        showDeleteChainClothConfirm"
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

        {{-- Delete Rib Size Modal --}}
        <div x-show="showDeleteRibSizeConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeleteRibSizeConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Rib Size?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this texture? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteRibSizeConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/rib-sizes/' + showDeleteRibSizeConfirm"
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

        {{-- Delete Print Ink Modal --}}
        <div x-show="showDeletePrintInkConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeletePrintInkConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Print Ink?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this sleeve? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeletePrintInkConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/print-inks/' + showDeletePrintInkConfirm"
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

        {{-- Delete Finishing Modal --}}
        <div x-show="showDeleteFinishingConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeleteFinishingConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Finishing?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this size? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteFinishingConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/finishings/' + showDeleteFinishingConfirm"
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

        {{-- Delete NeckOverdeck Modal --}}
        <div x-show="showDeleteNeckOverdeckConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeleteNeckOverdeckConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete NeckOverdeck?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this service? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteNeckOverdeckConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/neck-overdecks/' +
                        showDeleteNeckOverdeckConfirm"
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

        {{-- Delete UnderarmOverdeck Modal --}}
        <div x-show="showDeleteUnderarmOverdeckConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeleteUnderarmOverdeckConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete UnderarmOverdeck?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this item? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteUnderarmOverdeckConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/underarm-overdecks/' +
                        showDeleteUnderarmOverdeckConfirm"
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

        {{-- Delete SideSplit Modal --}}
        <div x-show="showDeleteSideSplitConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeleteSideSplitConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete SideSplit?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this item? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteSideSplitConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/side-splits/' +
                        showDeleteSideSplitConfirm"
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

        {{-- Delete SewingLabel Modal --}}
        <div x-show="showDeleteSewingLabelConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeleteSewingLabelConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete SewingLabel?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this item? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteSewingLabelConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/sewing-labels/' +
                        showDeleteSewingLabelConfirm"
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

        {{-- Delete PlasticPacking Modal --}}
        <div x-show="showDeletePlasticPackingConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeletePlasticPackingConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete PlasticPacking?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this item? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeletePlasticPackingConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/plastic-packings/' +
                        showDeletePlasticPackingConfirm"
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

        {{-- Delete Sticker Modal --}}
        <div x-show="showDeleteStickerConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
            style="background-color: rgba(0, 0, 0, 0.5);">
            <div @click.away="showDeleteStickerConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Sticker?</h3>
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this item? This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteStickerConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form
                        :action="'{{ route('owner.manage-data.work-orders.index') }}/stickers/' +
                        showDeleteStickerConfirm"
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

        {{-- AJAX Pagination Script --}}
        <script>
            // Global function to setup all pagination containers
            function setupAllPagination() {
                const paginationContainers = [
                    'cutting-pattern-pagination-container',
                    'chain-cloth-pagination-container',
                    'rib-size-pagination-container',
                    'print-ink-pagination-container',
                    'finishing-pagination-container',
                    'neck-overdeck-pagination-container',
                    'underarm-overdeck-pagination-container',
                    'side-split-pagination-container',
                    'sewing-label-pagination-container',
                    'plastic-packing-pagination-container',
                    'sticker-pagination-container'
                ];

                paginationContainers.forEach(containerId => {
                    const container = document.getElementById(containerId);
                    if (!container) return;

                    // Prevent duplicate listeners
                    if (container._paginationListener) return;
                    container._paginationListener = true;

                    container.addEventListener('click', function(e) {
                        const link = e.target.closest('a[href*="page="]');
                        if (!link) return;

                        e.preventDefault();
                        const url = link.href;

                        // Show loading indicator
                        if (typeof NProgress !== 'undefined') NProgress.start();

                        fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newContent = doc.getElementById(containerId);

                                if (newContent) {
                                    container.innerHTML = newContent.innerHTML;

                                    // Scroll to section top
                                    const sectionId = containerId.replace('-pagination-container', 's');
                                    const section = document.getElementById(sectionId) || document.getElementById(
                                        containerId.replace('-pagination-container', ''));
                                    if (section) {
                                        setTimeout(() => {
                                            section.scrollIntoView({
                                                behavior: 'smooth',
                                                block: 'start'
                                            });
                                        }, 100);
                                    }
                                }

                                if (typeof NProgress !== 'undefined') NProgress.done();
                            })
                            .catch(error => {
                                console.error('Pagination error:', error);
                                if (typeof NProgress !== 'undefined') NProgress.done();
                            });
                    });
                });
            }

            // Setup pagination on different events to handle Turbo navigation
            document.addEventListener('DOMContentLoaded', setupAllPagination);
            document.addEventListener('turbo:load', setupAllPagination);
            document.addEventListener('turbo:render', setupAllPagination);
        </script>

    </div>
@endsection
