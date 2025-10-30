@extends('layouts.app')

@section('title', 'Create Orders')

@section('content')

    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    <x-nav-locate :items="[$root, 'Orders', 'Create Order']" />

    <form x-data="orderForm()" @customer_id-selected.window="customer_id = $event.detail"
        @sales_id-selected.window="sales_id = $event.detail"
        @product_category_id-selected.window="product_category_id = $event.detail"
        @material_category_id-selected.window="material_category_id = $event.detail"
        @material_texture_id-selected.window="material_texture_id = $event.detail"
        @shipping_id-selected.window="shipping_id = $event.detail" method="POST" action="{{ route('admin.orders.store') }}"
        class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6 space-y-6 md:space-y-8">
        @csrf

        {{-- ================= Header ================= --}}
        <div class="space-y-6 border-b border-gray-200 pb-6 md:pb-8">
            <h2 class="text-xl font-semibold text-gray-900">Create Order</h2>
        </div>

        {{-- Priority, Order Date & Deadline --}}
        <div class="border-b border-gray-200 pb-8 md:pb-12">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                {{-- Priority --}}
                <div class="space-y-2">
                    <label for="priority" class="block text-sm font-medium text-gray-600">Priority</label>
                    <select id="priority" name="priority" x-model="priority"
                        class="w-full rounded-md px-3 py-2 text-sm border border-gray-300 focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 text-gray-700">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                    </select>
                </div>

                {{-- Order Date --}}
                <div class="space-y-2">
                    <label for="order_date" class="block text-sm font-medium text-gray-600">Order Date</label>
                    <div class="relative">
                        <input id="order_date" name="order_date" type="date" x-model="order_date"
                            value="{{ old('order_date', date('Y-m-d')) }}"
                            class="w-full rounded-md px-3 py-2 text-sm border border-gray-300 focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 text-gray-700" />
                        @error('order_date')
                            <p class="absolute left-0 -bottom-5 text-[10px] md:text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Deadline --}}
                <div class="space-y-2">
                    <label for="deadline" class="block text-sm font-medium text-gray-600">Deadline</label>
                    <div class="relative">
                        <input id="deadline" name="deadline" type="date" x-model="deadline"
                            value="{{ old('deadline') }}"
                            class="w-full rounded-md px-3 py-2 text-sm border border-gray-300 focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2 text-gray-700" />
                        @error('deadline')
                            <p class="absolute left-0 -bottom-5 text-[10px] md:text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= Customers & Sales ================= --}}
        <section class="space-y-4 md:space-y-5 border-b border-gray-200 pb-8 md:pb-12">
            <h3 class="text-lg font-semibold text-gray-800">Data Customers & Sales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <div class="relative flex flex-col md:flex-row md:items-start gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24 md:mt-2">Customer</label>

                    <div class="w-full space-y-2">
                        <x-select-form name="customer_id" label="Customer" placeholder="-- Select Customer --"
                            :options="$customers" display="customer_name" :old="old('customer_id')" />

                        <button type="button" @click="$dispatch('open-add-customer')"
                            class="text-sm text-primary hover:text-primary-dark font-medium flex items-center gap-1 group">
                            <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add new customer
                        </button>
                    </div>
                </div>

                <div class="relative flex flex-col md:flex-row md:items-start gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24 md:mt-2">Sales</label>

                    <x-select-form name="sales_id" label="Sales" placeholder="-- Select Sales --" :options="$sales"
                        display="sales_name" :old="old('sales_id')" />
                </div>


            </div>
        </section>

        {{-- ================= Detail Products ================= --}}
        <section class="space-y-4 md:space-y-5 border-b border-gray-200 pb-8 md:pb-12">
            <h3 class="text-lg font-semibold text-gray-800">Detail Products</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <div class="space-y-7">
                    {{-- Product --}}
                    <div class="relative flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                        <label class="text-sm text-gray-600 md:w-24">Product</label>

                        <x-select-form name="product_category_id" label="Product" placeholder="-- Select Product --"
                            :options="$productCategories" display="product_name" :old="old('product_category_id')" />
                    </div>

                    {{-- Color --}}
                    <div class="relative flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                        <label class="text-sm text-gray-600 md:w-24">Color</label>
                        <div class="relative w-full">
                            <input type="text" name="product_color" x-model="product_color"
                                value="{{ old('product_color') }}"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700
                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="Enter color" />
                            @error('product_color')
                                <p class="absolute left-0 -bottom-5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Materials --}}
                    <div class="relative flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                        <label class="text-sm text-gray-600 md:w-24">Material</label>
                        <div class="flex flex-col md:flex-row gap-2 gap-y-6 md:gap-3 w-full">
                            <x-select-form name="material_category_id" label="Product" placeholder="-- Select Material --"
                                :options="$materialCategories" display="material_name" :old="old('material_category_id')" />

                            <x-select-form name="material_texture_id" label="Product" placeholder="-- Select Texture --"
                                :options="$materialTextures" display="texture_name" :old="old('material_texture_id')" />
                        </div>
                    </div>

                </div>

                {{-- Notes --}}
                <div class="flex flex-col md:flex-row md:items-start gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-14">Note</label>
                    <div class="relative w-full md:flex-1">
                        <textarea rows="3" name="notes" x-model="notes"
                            class="w-full min-h-[165px] rounded-md border border-gray-300 px-3 py-2 text-sm
                            focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
                            placeholder="Write notes here...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="absolute left-0 -bottom-5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= Detail Orders ================= --}}
        <section class="space-y-4 md:space-y-5 border-b border-gray-200 pb-8">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Detail Orders</h3>
                @error('designs')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Design Variants List --}}
            <template x-for="(design, dIndex) in designVariants" :key="dIndex">
                <div class="border border-gray-300 rounded-lg p-4 relative">
                    {{-- Delete Design Variant --}}
                    <button type="button"
                        @click="
                            let msg = design.name.trim() 
                                ? `Are you sure you want to delete ${design.name} design?` 
                                : 'Are you sure you want to delete this design variant?';
                            if (confirm(msg)) designVariants.splice(dIndex, 1)
                        "
                        class="absolute top-2 right-2 p-2 rounded-md text-gray-500 hover:text-red-600">
                        ✕
                    </button>


                    {{-- Design Name & Add Sleeve Variant --}}
                    <div class="flex flex-col md:flex-row gap-3 mt-8 md:mt-0">
                        <div class="relative w-full md:w-72">
                            <input type="text" placeholder="Design Name" x-model="design.name"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary/20 focus:outline-none focus:ring-2" />
                            <span x-show="design.error" x-text="design.error"
                                class="absolute left-0 -bottom-5 text-xs text-red-600"></span>
                        </div>

                        <div class="flex flex-col md:flex-row gap-2 items-start md:items-center">
                            <button type="button" @click="if(design.name.trim() !== '') addSleeveVariant(dIndex)"
                                :class="design.name.trim() === '' ?
                                    'cursor-not-allowed bg-gray-300 text-white' :
                                    'bg-primary hover:bg-primary-dark text-white'"
                                class="px-3 py-2 rounded-md text-sm whitespace-nowrap w-full md:w-auto">
                                + Add Sleeve
                            </button>
                            <span class="italic text-xs text-gray-400 hidden md:inline">(Fill design name first)</span>
                        </div>
                    </div>


                    {{-- Sleeve Variants List --}}
                    <template x-for="(variant, vIndex) in design.sleeveVariants" :key="vIndex">
                        <div class="border border-gray-200 rounded-lg p-4 space-y-4 mt-4 relative">
                            {{-- Delete Sleeve Variant --}}
                            <button type="button"
                                @click="
                                    if (confirm('Are you sure you want to delete this sleeve?')) {
                                        design.sleeveVariants.splice(vIndex, 1)
                                    }
                                "
                                class="absolute top-2 right-2 p-2 rounded-md text-gray-500 hover:text-red-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div class="space-y-3">
                                <div class="flex flex-col md:flex-row md:justify-start md:items-start gap-3">
                                    {{-- Sleeve --}}
                                    <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-8 mt-4 md:mt-0">
                                        <label class="text-sm text-gray-600">Sleeve</label>
                                        <div class="relative w-full md:w-56" x-data="sleeveSelect(dIndex, vIndex, variant.sleeve)"
                                            @sleeve-selected.window="if($event.detail.dIndex === dIndex && $event.detail.vIndex === vIndex) variant.sleeve = $event.detail.value">

                                            {{-- Custom Select Button --}}
                                            <button type="button" @click="open = !open"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-left bg-white
                                                focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary flex justify-between items-center">
                                                <span x-text="selected ? selected.sleeve_name : '-- Select Sleeve --'"
                                                    :class="!selected ? 'text-gray-400' : 'text-gray-900'"></span>
                                                <svg class="w-4 h-4 text-gray-400" :class="open && 'rotate-180'"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            {{-- Dropdown --}}
                                            <div x-show="open" @click.away="open = false" x-cloak
                                                class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg">
                                                <div class="relative px-4 py-2">
                                                    <div
                                                        class="absolute inset-y-0 left-7 flex items-center pointer-events-none">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </div>
                                                    <input type="text" x-model="search" placeholder="Search..."
                                                        class="block w-full h-10 pl-10 pr-3 text-gray-600 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary/50 focus:border-primary">
                                                </div>
                                                <ul class="max-h-60 overflow-y-auto">
                                                    <template x-for="sleeve in filteredOptions" :key="sleeve.id">
                                                        <li @click="selectSleeve(sleeve)"
                                                            class="px-5 py-2 text-sm hover:bg-primary/5 cursor-pointer"
                                                            :class="variant.sleeve == sleeve.id ? 'bg-primary/10 font-medium' :
                                                                ''"
                                                            x-text="sleeve.sleeve_name">
                                                        </li>
                                                    </template>
                                                    <li x-show="filteredOptions.length === 0"
                                                        class="px-5 py-2 text-sm text-gray-400 text-center">
                                                        No results found
                                                    </li>
                                                </ul>
                                            </div>

                                            <span x-show="variant.error" x-text="variant.error"
                                                class="absolute left-0 -bottom-5 text-xs text-red-600"></span>
                                        </div>
                                    </div>

                                    {{-- Base Price --}}
                                    <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                                        <label class="text-sm text-gray-600">Base Price</label>
                                        <div class="relative">
                                            <div class="relative">
                                                <span
                                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm font-medium">
                                                    Rp
                                                </span>
                                                <input type="text" :value="variant.basePriceDisplay || ''"
                                                    @input="
                                                        let value = $event.target.value.replace(/[^0-9]/g, '');
                                                        variant.basePrice = value ? parseInt(value) : 0;
                                                        variant.basePriceDisplay = value ? parseInt(value).toLocaleString('id-ID') : '';
                                                        $event.target.value = variant.basePriceDisplay;
                                                        updateUnitPrices(dIndex, vIndex);
                                                    "
                                                    placeholder="0"
                                                    class="w-full md:w-40 rounded-md border border-gray-300 pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                            </div>
                                            <span x-show="variant.basePriceError" x-text="variant.basePriceError"
                                                class="absolute left-0 -bottom-5 text-xs text-red-600"></span>
                                        </div>
                                    </div>

                                    {{-- Add Size --}}
                                    <div class="flex flex-col md:flex-row md:items-center gap-2">
                                        <button type="button"
                                            @click="if(variant.sleeve !== '' && variant.basePrice > 0) { openModal = 'addSize'; selectedDesign = dIndex; selectedVariant = vIndex }"
                                            :class="(variant.sleeve === '' || variant.basePrice <= 0) ?
                                            'cursor-not-allowed bg-gray-300 text-white' :
                                            'bg-primary hover:bg-primary-dark text-white'"
                                            class="w-full md:w-auto px-3 py-2 rounded-md text-sm whitespace-nowrap">
                                            + Add Size
                                        </button>
                                        <span class="italic text-xs text-gray-400 hidden md:inline">(Select sleeve & set
                                            base price
                                            first)</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Table --}}
                            <div class="overflow-x-auto -mx-4 md:mx-0">
                                <table class="w-full text-sm min-w-[640px]">
                                    <thead class="bg-primary-light text-gray-600">
                                        <tr>
                                            <th class="py-2 px-4 text-left w-12 rounded-l-lg">No</th>
                                            <th class="py-2 px-4 text-left min-w-[100px]">Size</th>
                                            <th class="py-2 px-4 text-left min-w-[160px]">Unit Price</th>
                                            <th class="py-2 px-4 text-left min-w-[140px]">QTY</th>
                                            <th class="py-2 px-4 text-left min-w-[140px]">Total Price</th>
                                            <th class="py-2 px-4 text-right w-16 rounded-r-lg">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, rIndex) in variant.rows" :key="rIndex">
                                            <tr class="border-t border-gray-200">
                                                <td class="py-2 px-4" x-text="rIndex+1"></td>
                                                <td class="py-2 px-4">
                                                    <span x-text="row.size"></span>
                                                    <span class="text-xs text-gray-500" x-show="row.extraPrice > 0">
                                                        (+<span x-text="row.extraPrice.toLocaleString('id-ID')"></span>)
                                                    </span>
                                                </td>
                                                <td class="py-2 px-4">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-gray-600 text-sm">Rp</span>
                                                        <input type="text" :value="row.unitPriceDisplay || ''"
                                                            @input="
                                                                let value = $event.target.value.replace(/[^0-9]/g, '');
                                                                row.unitPrice = value ? parseInt(value) : 0;
                                                                row.unitPriceDisplay = value ? parseInt(value).toLocaleString('id-ID') : '';
                                                                $event.target.value = row.unitPriceDisplay;
                                                            "
                                                            :class="row.unitPrice === 0 ? 'border-red-500' : 'border-gray-300'"
                                                            class="w-32 rounded-md border px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                                    </div>
                                                </td>
                                                <td class="py-2 px-4">
                                                    <div class="flex items-center gap-2">
                                                        <input type="number" x-model.number="row.qty" min="0"
                                                            @focus="if(row.qty == 0) row.qty = ''"
                                                            @blur="if(row.qty === '' || row.qty === null) row.qty = 0"
                                                            :class="row.qty === 0 ? 'border-red-500' : 'border-gray-300'"
                                                            class="w-20 rounded-md border px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                                        <span x-show="row.qty === 0"
                                                            class="text-xs text-red-600 whitespace-nowrap">QTY
                                                            required</span>
                                                    </div>
                                                </td>
                                                <td class="py-2 px-4 font-medium text-gray-900"
                                                    x-text="'Rp ' + (row.unitPrice * row.qty).toLocaleString('id-ID')">
                                                </td>
                                                <td class="py-2 px-4 text-right">
                                                    <button type="button" @click="variant.rows.splice(rIndex, 1)"
                                                        class="p-2 rounded-md bg-red-500 text-white hover:bg-red-600">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="variant.rows.length === 0">
                                            <td colspan="6" class="py-3 px-4 text-center text-gray-400">
                                                No sizes added yet.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Button Add Design Variant --}}
            <button type="button" @click="addDesignVariant()"
                class="w-full md:w-auto px-3 py-2 rounded-md text-sm font-medium cursor-pointer bg-primary hover:bg-green-700 text-white">
                + Add Design Variant
            </button>
            {{-- ================= Modal Add Size ================= --}}
            <div x-show="openModal === 'addSize'" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500/50 backdrop-blur-sm px-4">
                <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-3xl p-6 space-y-5">
                    <div class="flex justify-between items-center pb-4">
                        <h3 class="text-xl font-semibold text-gray-900">Select Sizes</h3>
                        <button type="button" @click="openModal=null"
                            class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Size Cards --}}
                    <div
                        class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3 max-h-96 overflow-y-auto py-2">
                        <template x-for="size in sizes" :key="size.id">
                            <div @click="toggleSize(size)"
                                :class="selectedSizes.find(s => s.id === size.id) ?
                                    'bg-primary text-white border-primary shadow-md scale-105' :
                                    'bg-white text-gray-700 border-gray-300 hover:border-primary/50 hover:shadow-sm'"
                                class="cursor-pointer rounded-lg border-2 px-4 py-3 text-center text-sm font-medium transition-all duration-200">
                                <span x-text="size.size_name" class="block"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Footer --}}
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" @click="openModal=null"
                            class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="button" @click="applySizes"
                            class="px-5 py-2.5 rounded-lg bg-primary text-white hover:bg-primary-dark font-medium transition-colors shadow-sm">
                            Add Size
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= Additionals & Final ================= --}}
        <section class="space-y-4 md:space-y-5 border-b border-gray-200 pb-8 md:pb-12">
            <h3 class="text-lg font-semibold text-gray-800">Additionals</h3>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-x-8 gap-y-6">
                <div>
                    <label class="block text-sm text-gray-600 mb-2">Additionals</label>

                    {{-- List Input Additionals --}}
                    <template x-for="(item, index) in additionals" :key="index">
                        <div class="flex flex-col gap-3 mb-4">
                            <div class="flex flex-col md:flex-row gap-3">
                                <div class="flex-1 relative" x-data="additionalServiceSelect(index, item.service_id)"
                                    @service-selected.window="if($event.detail.index === index) item.service_id = $event.detail.value">

                                    {{-- Custom Select Button --}}
                                    <button type="button" @click="open = !open"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-left bg-white
                                        focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary flex justify-between items-center">
                                        <span x-text="selected ? selected.service_name : '-- Select Service --'"
                                            :class="!selected ? 'text-gray-400' : 'text-gray-900'"></span>
                                        <svg class="w-4 h-4 text-gray-400" :class="open && 'rotate-180'" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    {{-- Dropdown --}}
                                    <div x-show="open" @click.away="open = false" x-cloak
                                        class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg">
                                        <div class="relative px-4 py-2">
                                            <div class="absolute inset-y-0 left-7 flex items-center pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                </svg>
                                            </div>
                                            <input type="text" x-model="search" placeholder="Search..."
                                                class="block w-full h-10 pl-10 pr-3 text-gray-600 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary/50 focus:border-primary">
                                        </div>
                                        <ul class="max-h-60 overflow-y-auto">
                                            <template x-for="service in filteredOptions" :key="service.id">
                                                <li @click="selectService(service)"
                                                    class="px-5 py-2 text-sm hover:bg-primary/5 cursor-pointer"
                                                    :class="item.service_id == service.id ? 'bg-primary/10 font-medium' : ''"
                                                    x-text="service.service_name">
                                                </li>
                                            </template>
                                            <li x-show="filteredOptions.length === 0"
                                                class="px-5 py-2 text-sm text-gray-400 text-center">
                                                No results found
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="relative w-full md:w-40">
                                    <div class="relative">
                                        <span
                                            class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm font-medium">
                                            Rp
                                        </span>
                                        <input type="text" :value="item.priceDisplay || ''"
                                            @input="
                                                let value = $event.target.value.replace(/[^0-9]/g, '');
                                                item.price = value ? parseInt(value) : 0;
                                                item.priceDisplay = value ? parseInt(value).toLocaleString('id-ID') : '';
                                                $event.target.value = item.priceDisplay;
                                            "
                                            placeholder="0"
                                            class="w-full rounded-md border border-gray-300 pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                                    </div>
                                </div>

                                <button type="button" @click="removeAdditional(index)"
                                    class="w-full md:w-auto p-2 rounded-md bg-red-500 text-white hover:bg-red-600 transition-colors">
                                    <svg class="w-5 h-5 mx-auto md:mx-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Error Messages --}}
                            <div class="flex flex-col gap-1">
                                <template x-if="index === 0">
                                    <div>
                                        @error('additionals.0.service_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('additionals.0.price')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </template>
                                <template x-if="index === 1">
                                    <div>
                                        @error('additionals.1.service_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('additionals.1.price')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </template>
                                <template x-if="index === 2">
                                    <div>
                                        @error('additionals.2.service_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('additionals.2.price')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </template>
                                <template x-if="index === 3">
                                    <div>
                                        @error('additionals.3.service_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('additionals.3.price')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </template>
                                <template x-if="index === 4">
                                    <div>
                                        @error('additionals.4.service_id')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        @error('additionals.4.price')
                                            <p class="text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </template>
                                <template x-if="index >= 5">
                                    <div>
                                        <p class="text-xs text-red-600">Please check additional service #<span
                                                x-text="index + 1"></span></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Button Add --}}
                    <button type="button" @click="addAdditional"
                        class="w-full md:w-auto px-6 py-2 rounded-md bg-primary text-white hover:bg-primary-dark text-sm font-medium">
                        + Add Additional
                    </button>
                </div>

                {{-- Shipping --}}
                <div class="relative flex flex-col gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24">Shipping</label>

                    <x-select-form name="shipping_id" label="Shipping" placeholder="-- Select Shipping --"
                        :options="$shippings" display="shipping_name" :old="old('shipping_id')" />
                </div>

            </div>
        </section>

        {{-- ================= Discount, Final Price & Create ================= --}}
        <div class="flex justify-end mt-6">
            <div class="w-full lg:w-1/2 xl:w-1/3 space-y-4">
                <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24">Discount</label>
                    <div class="relative w-full">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm font-medium">
                            Rp
                        </span>
                        <input type="text" :value="discountDisplay || ''"
                            @input="
                                let value = $event.target.value.replace(/[^0-9]/g, '');
                                discount = value ? parseInt(value) : 0;
                                discountDisplay = value ? parseInt(value).toLocaleString('id-ID') : '';
                                $event.target.value = discountDisplay;
                            "
                            placeholder="0"
                            class="w-full rounded-md border border-gray-300 pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-200 focus:border-green-400" />
                    </div>
                </div>
                <div class="border border-gray-200 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600">Final Price</p>
                    <p class="text-2xl md:text-lg font-bold text-gray-900"
                        x-text="'Rp ' + getFinalPrice().toLocaleString('id-ID')">
                    </p>
                </div>
                <button type="submit"
                    class="w-full px-4 py-3 md:py-2 rounded-md bg-primary text-white hover:bg-primary-dark text-base md:text-sm font-medium">
                    Create Order
                </button>
            </div>
        </div>

        {{-- Hidden inputs for design variants and additionals --}}
        <template x-for="(design, dIndex) in designVariants" :key="'design-' + dIndex">
            <div>
                <input type="hidden" :name="'designs[' + dIndex + '][name]'" x-model="design.name">
                <template x-for="(variant, vIndex) in design.sleeveVariants" :key="'variant-' + dIndex + '-' + vIndex">
                    <div>
                        <template x-for="(row, rIndex) in variant.rows"
                            :key="'row-' + dIndex + '-' + vIndex + '-' + rIndex">
                            <div>
                                <input type="hidden"
                                    :name="'designs[' + dIndex + '][items][' + (vIndex * 100 + rIndex) + '][design_name]'"
                                    x-model="design.name">
                                <input type="hidden"
                                    :name="'designs[' + dIndex + '][items][' + (vIndex * 100 + rIndex) + '][sleeve_id]'"
                                    x-model="variant.sleeve">
                                <input type="hidden"
                                    :name="'designs[' + dIndex + '][items][' + (vIndex * 100 + rIndex) + '][size_id]'"
                                    x-model="row.size_id">
                                <input type="hidden"
                                    :name="'designs[' + dIndex + '][items][' + (vIndex * 100 + rIndex) + '][qty]'"
                                    x-model="row.qty">
                                <input type="hidden"
                                    :name="'designs[' + dIndex + '][items][' + (vIndex * 100 + rIndex) + '][unit_price]'"
                                    x-model="row.unitPrice">
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>

        <template x-for="(item, index) in additionals" :key="'add-' + index">
            <div>
                <input type="hidden" :name="'additionals[' + index + '][service_id]'" x-model="item.service_id">
                <input type="hidden" :name="'additionals[' + index + '][price]'" x-model="item.price">
            </div>
        </template>

        {{-- Hidden calculated totals --}}
        <input type="hidden" name="total_qty"
            :value="designVariants.reduce((sum, d) => sum + d.sleeveVariants.reduce((s, v) => s + v.rows.reduce((r, row) => r +
                parseInt(row.qty || 0), 0), 0), 0)">
        <input type="hidden" name="subtotal" :value="getSubTotal()">
        <input type="hidden" name="discount" :value="discount || 0">
        <input type="hidden" name="grand_total" :value="getFinalPrice()">

    </form>

    {{-- ===================== MODAL ADD CUSTOMER (Outside main form) ===================== --}}
    <div x-data="{
        openModal: '{{ session('openModal') }}',
        addCustomerForm: {
            customer_name: '{{ old('customer_name') }}',
            phone: '{{ old('phone') }}',
            province_id: '{{ old('province_id') }}',
            city_id: '{{ old('city_id') }}',
            district_id: '{{ old('district_id') }}',
            village_id: '{{ old('village_id') }}',
            address: '{{ old('address') }}'
        },
        addCustomerErrors: {},
        addProvince: '{{ old('province_id') }}',
        addCity: '{{ old('city_id') }}',
        addDistrict: '{{ old('district_id') }}',
        addVillage: '{{ old('village_id') }}',
        addCities: [],
        addDistricts: [],
        addVillages: [],
    
        async init() {
            // Restore Add Customer state from old input
            const oldProvince = '{{ old('province_id') }}' ? '{{ old('province_id') }}' : '';
            const oldCity = '{{ old('city_id') }}' ? '{{ old('city_id') }}' : '';
            const oldDistrict = '{{ old('district_id') }}' ? '{{ old('district_id') }}' : '';
            const oldVillage = '{{ old('village_id') }}' ? '{{ old('village_id') }}' : '';
    
            if (oldProvince) {
                this.addProvince = oldProvince;
                await this.fetchCities(oldProvince);
    
                if (oldCity) {
                    await new Promise(resolve => setTimeout(resolve, 100));
                    this.addCity = oldCity;
                    await this.fetchDistricts(oldCity);
    
                    if (oldDistrict) {
                        await new Promise(resolve => setTimeout(resolve, 100));
                        this.addDistrict = oldDistrict;
                        await this.fetchVillages(oldDistrict);
    
                        if (oldVillage) {
                            await new Promise(resolve => setTimeout(resolve, 100));
                            this.addVillage = oldVillage;
                        }
                    }
                }
            }
        },
    
        validateAddCustomer() {
            this.addCustomerErrors = {};
    
            if (!this.addCustomerForm.customer_name?.trim()) {
                this.addCustomerErrors.customer_name = 'Customer name is required';
            } else if (this.addCustomerForm.customer_name.length > 100) {
                this.addCustomerErrors.customer_name = 'Customer name must not exceed 100 characters';
            }
    
            if (!this.addCustomerForm.phone?.trim()) {
                this.addCustomerErrors.phone = 'Phone is required';
            } else if (this.addCustomerForm.phone.length > 20) {
                this.addCustomerErrors.phone = 'Phone must not exceed 20 characters';
            }
    
            if (!this.addProvince) {
                this.addCustomerErrors.province_id = 'Province is required';
            }
    
            if (!this.addCity) {
                this.addCustomerErrors.city_id = 'City is required';
            }
    
            if (!this.addDistrict) {
                this.addCustomerErrors.district_id = 'District is required';
            }
    
            if (!this.addVillage) {
                this.addCustomerErrors.village_id = 'Village is required';
            }
    
            if (!this.addCustomerForm.address?.trim()) {
                this.addCustomerErrors.address = 'Address is required';
            } else if (this.addCustomerForm.address.length > 255) {
                this.addCustomerErrors.address = 'Address must not exceed 255 characters';
            }
    
            return Object.keys(this.addCustomerErrors).length === 0;
        },
    
        async fetchCities(provinceId) {
            if (!provinceId) {
                this.addCities = [];
                this.addDistricts = [];
                this.addVillages = [];
                this.addCity = '';
                this.addDistrict = '';
                this.addVillage = '';
                return;
            }
            try {
                const response = await fetch(`/admin/customers/api/cities/${provinceId}`);
                const data = await response.json();
                this.addCities = data;
                // Reset dependent fields
                this.addDistricts = [];
                this.addVillages = [];
                this.addCity = '';
                this.addDistrict = '';
                this.addVillage = '';
            } catch (error) {
                console.error('Error fetching cities:', error);
            }
        },
    
        async fetchDistricts(cityId) {
            if (!cityId) {
                this.addDistricts = [];
                this.addVillages = [];
                this.addDistrict = '';
                this.addVillage = '';
                return;
            }
            try {
                const response = await fetch(`/admin/customers/api/districts/${cityId}`);
                const data = await response.json();
                this.addDistricts = data;
                // Reset dependent fields
                this.addVillages = [];
                this.addDistrict = '';
                this.addVillage = '';
            } catch (error) {
                console.error('Error fetching districts:', error);
            }
        },
    
        async fetchVillages(districtId) {
            if (!districtId) {
                this.addVillages = [];
                this.addVillage = '';
                return;
            }
            try {
                const response = await fetch(`/admin/customers/api/villages/${districtId}`);
                const data = await response.json();
                this.addVillages = data;
            } catch (error) {
                console.error('Error fetching villages:', error);
            }
        }
    }" @keydown.escape.window="openModal = ''"
        @open-add-customer.window="openModal = 'addCustomer'">
        <div x-show="openModal === 'addCustomer'" x-transition.opacity x-cloak
            class="fixed inset-0 z-50 overflow-y-auto bg-gray-500/50 backdrop-blur-sm">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div @click.away="openModal = ''" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                    {{-- Header --}}
                    <div class="flex items-center justify-between p-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Add Customer</h3>
                        <button @click="openModal = ''" type="button"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer">
                            ✕
                        </button>
                    </div>

                    {{-- Form --}}
                    <form action="{{ route('admin.customers.store') }}" method="POST"
                        @submit="if (!validateAddCustomer()) $event.preventDefault()">
                        @csrf
                        <input type="hidden" name="from_create_order" value="1">
                        <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                            {{-- Customer Name --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Customer Name <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                                    x-model="addCustomerForm.customer_name" @blur="validateAddCustomer()"
                                    :class="addCustomerErrors.customer_name ||
                                        {{ $errors->addCustomer->has('customer_name') ? 'true' : 'false' }} ?
                                        'w-full rounded-md px-4 py-2 text-sm border border-red-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 text-gray-700' :
                                        'w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-gray-700'" />
                                <p x-show="addCustomerErrors.customer_name" x-text="addCustomerErrors.customer_name"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('customer_name', 'addCustomer')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Phone --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Phone <span class="text-red-600">*</span>
                                </label>
                                <input type="text" name="phone" value="{{ old('phone') }}"
                                    x-model="addCustomerForm.phone" @blur="validateAddCustomer()"
                                    :class="addCustomerErrors.phone ||
                                        {{ $errors->addCustomer->has('phone') ? 'true' : 'false' }} ?
                                        'w-full rounded-md px-4 py-2 text-sm border border-red-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 text-gray-700' :
                                        'w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-gray-700'" />
                                <p x-show="addCustomerErrors.phone" x-text="addCustomerErrors.phone"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('phone', 'addCustomer')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Province --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Province <span class="text-red-600">*</span>
                                </label>
                                <select x-model="addProvince" name="province_id"
                                    @change="addCustomerForm.province_id = addProvince; fetchCities(addProvince)"
                                    @blur="validateAddCustomer()"
                                    :class="addCustomerErrors.province_id ||
                                        {{ $errors->addCustomer->has('province_id') ? 'true' : 'false' }} ?
                                        'w-full rounded-md px-4 py-2 text-sm border border-red-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 text-gray-700' :
                                        'w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-gray-700'">
                                    <option value="">Select Province</option>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province->id }}"
                                            {{ old('province_id') == $province->id ? 'selected' : '' }}>
                                            {{ $province->province_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p x-show="addCustomerErrors.province_id" x-text="addCustomerErrors.province_id"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('province_id', 'addCustomer')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- City --}}
                            <div x-show="addProvince" x-transition>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    City <span class="text-red-600">*</span>
                                </label>
                                <select x-model="addCity" name="city_id"
                                    @change="addCustomerForm.city_id = addCity; fetchDistricts(addCity)"
                                    @blur="validateAddCustomer()"
                                    :class="addCustomerErrors.city_id ||
                                        {{ $errors->addCustomer->has('city_id') ? 'true' : 'false' }} ?
                                        'w-full rounded-md px-4 py-2 text-sm border border-red-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 text-gray-700' :
                                        'w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-gray-700'">
                                    <option value="">Select City</option>
                                    <template x-for="city in addCities" :key="city.id">
                                        <option :value="city.id" x-text="city.city_name"></option>
                                    </template>
                                </select>
                                <p x-show="addCustomerErrors.city_id" x-text="addCustomerErrors.city_id"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('city_id', 'addCustomer')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- District --}}
                            <div x-show="addCity" x-transition>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    District <span class="text-red-600">*</span>
                                </label>
                                <select x-model="addDistrict" name="district_id"
                                    @change="addCustomerForm.district_id = addDistrict; fetchVillages(addDistrict)"
                                    @blur="validateAddCustomer()"
                                    :class="addCustomerErrors.district_id ||
                                        {{ $errors->addCustomer->has('district_id') ? 'true' : 'false' }} ?
                                        'w-full rounded-md px-4 py-2 text-sm border border-red-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 text-gray-700' :
                                        'w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-gray-700'">
                                    <option value="">Select District</option>
                                    <template x-for="district in addDistricts" :key="district.id">
                                        <option :value="district.id" x-text="district.district_name"></option>
                                    </template>
                                </select>
                                <p x-show="addCustomerErrors.district_id" x-text="addCustomerErrors.district_id"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('district_id', 'addCustomer')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Village --}}
                            <div x-show="addDistrict" x-transition>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Village <span class="text-red-600">*</span>
                                </label>
                                <select x-model="addVillage" name="village_id"
                                    @change="addCustomerForm.village_id = addVillage" @blur="validateAddCustomer()"
                                    :class="addCustomerErrors.village_id ||
                                        {{ $errors->addCustomer->has('village_id') ? 'true' : 'false' }} ?
                                        'w-full rounded-md px-4 py-2 text-sm border border-red-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 text-gray-700' :
                                        'w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-gray-700'">
                                    <option value="">Select Village</option>
                                    <template x-for="village in addVillages" :key="village.id">
                                        <option :value="village.id" x-text="village.village_name"></option>
                                    </template>
                                </select>
                                <p x-show="addCustomerErrors.village_id" x-text="addCustomerErrors.village_id"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('village_id', 'addCustomer')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Address --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Address Detail <span class="text-red-600">*</span>
                                </label>
                                <textarea name="address" rows="3" x-model="addCustomerForm.address" @blur="validateAddCustomer()"
                                    :class="addCustomerErrors.address ||
                                        {{ $errors->addCustomer->has('address') ? 'true' : 'false' }} ?
                                        'w-full rounded-md px-4 py-2 text-sm border border-red-500 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 text-gray-700' :
                                        'w-full rounded-md px-4 py-2 text-sm border border-gray-200 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 text-gray-700'">{{ old('address') }}</textarea>
                                <p x-show="addCustomerErrors.address" x-text="addCustomerErrors.address"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('address', 'addCustomer')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-200">
                            <button @click="openModal = ''" type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-dark">
                                Save Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        function searchSelect(options, oldValue, fieldName) {
            return {
                open: false,
                search: '',
                options,
                selected: null,
                selectedId: oldValue || '',
                fieldName,

                init() {
                    if (this.selectedId) {
                        this.selected = this.options.find(o => String(o.id) === String(this.selectedId)) || null;
                    }
                    // ⬇️ dispatch event biar orderForm tahu
                    this.$dispatch(`${this.fieldName}-selected`, this.selectedId || '');

                    // Listen untuk auto-select dari orderForm (untuk customer_id)
                    window.addEventListener(`${this.fieldName}-selected`, (e) => {
                        if (e.detail) {
                            const option = this.options.find(o => String(o.id) === String(e.detail));
                            if (option) {
                                this.selected = option;
                                this.selectedId = option.id;
                            }
                        }
                    });
                },

                select(option) {
                    this.selected = option;
                    this.selectedId = option.id;
                    this.open = false;

                    // ⬇️ dispatch lagi setiap kali select berubah
                    this.$dispatch(`${this.fieldName}-selected`, this.selectedId);
                }
            }
        }

        function sleeveSelect(dIndex, vIndex, initialValue) {
            return {
                open: false,
                search: '',
                sleeves: @json($materialSleeves),
                selected: null,
                dIndex,
                vIndex,

                get filteredOptions() {
                    if (!this.search) return this.sleeves;
                    return this.sleeves.filter(s =>
                        s.sleeve_name.toLowerCase().includes(this.search.toLowerCase())
                    );
                },

                init() {
                    if (initialValue) {
                        this.selected = this.sleeves.find(s => String(s.id) === String(initialValue)) || null;
                    }
                },

                selectSleeve(sleeve) {
                    this.selected = sleeve;
                    this.open = false;
                    this.$dispatch('sleeve-selected', {
                        dIndex: this.dIndex,
                        vIndex: this.vIndex,
                        value: sleeve.id
                    });
                }
            }
        }

        function additionalServiceSelect(index, initialValue) {
            return {
                open: false,
                search: '',
                services: @json($services),
                selected: null,
                index,

                get filteredOptions() {
                    if (!this.search) return this.services;
                    return this.services.filter(s =>
                        s.service_name.toLowerCase().includes(this.search.toLowerCase())
                    );
                },

                init() {
                    if (initialValue) {
                        this.selected = this.services.find(s => String(s.id) === String(initialValue)) || null;
                    }
                },

                selectService(service) {
                    this.selected = service;
                    this.open = false;
                    this.$dispatch('service-selected', {
                        index: this.index,
                        value: service.id
                    });
                }
            }
        }

        function orderForm() {
            return {
                // ====== STATE UTAMA ======
                priority: '{{ old('priority', 'normal') }}',
                order_date: '{{ old('order_date', date('Y-m-d')) }}',
                deadline: '{{ old('deadline') }}',
                customer_id: '{{ old('customer_id') }}',
                sales_id: '{{ old('sales_id') }}',
                product_category_id: '{{ old('product_category_id') }}',
                product_color: '{{ old('product_color') }}',
                material_category_id: '{{ old('material_category_id') }}',
                material_texture_id: '{{ old('material_texture_id') }}',
                notes: '{{ old('notes') }}',
                discount: {{ old('discount', 0) }},
                discountDisplay: '{{ old('discount') ? number_format(old('discount'), 0, ',', '.') : '' }}',
                shipping_id: '{{ old('shipping_id') }}',

                // ====== DETAIL ======
                designVariants: [],
                additionals: [],

                // REF DATA
                sizes: @json($materialSizes),
                sleeves: @json($materialSleeves),

                // MODAL STATE
                openModal: null,
                selectedDesign: null,
                selectedVariant: null,
                selectedSizes: [],

                // INIT
                init() {
                    // Set customer_id if new customer was just added
                    @if (session('select_customer_id'))
                        this.customer_id = '{{ session('select_customer_id') }}';
                        // Dispatch event to update select-form component
                        window.dispatchEvent(new CustomEvent('customer_id-selected', {
                            detail: '{{ session('select_customer_id') }}'
                        }));
                    @endif

                    // Restore from old input if validation fails
                    @if (old('designs'))
                        this.restoreFromOldInput();
                    @endif

                    @if (old('additionals'))
                        @foreach (old('additionals', []) as $index => $add)
                            this.additionals.push({
                                service_id: '{{ $add['service_id'] ?? '' }}',
                                price: {{ $add['price'] ?? 0 }},
                                priceDisplay: '{{ isset($add['price']) && $add['price'] > 0 ? number_format($add['price'], 0, ',', '.') : '' }}',
                                error: ''
                            });
                        @endforeach
                    @endif
                },

                // Restore design variants from old input
                restoreFromOldInput() {
                    const oldDesigns = @json(old('designs', []));
                    const designMap = {};

                    // Group items by design name
                    Object.values(oldDesigns).forEach(design => {
                        if (design.items) {
                            Object.values(design.items).forEach(item => {
                                const designName = item.design_name;
                                if (!designMap[designName]) {
                                    designMap[designName] = [];
                                }
                                designMap[designName].push(item);
                            });
                        }
                    });

                    // Rebuild design variants structure
                    Object.keys(designMap).forEach(designName => {
                        const items = designMap[designName];
                        const sleeveMap = {};

                        // Group by sleeve_id
                        items.forEach(item => {
                            if (!sleeveMap[item.sleeve_id]) {
                                sleeveMap[item.sleeve_id] = [];
                            }
                            sleeveMap[item.sleeve_id].push(item);
                        });

                        const sleeveVariants = [];
                        Object.keys(sleeveMap).forEach(sleeveId => {
                            const sleeveItems = sleeveMap[sleeveId];
                            const basePrice = sleeveItems[0].unit_price - (this.sizes.find(s => s.id ==
                                sleeveItems[0].size_id)?.extra_price || 0);

                            const rows = sleeveItems.map(item => {
                                const size = this.sizes.find(s => s.id == item.size_id);
                                const unitPrice = parseFloat(item.unit_price);
                                return {
                                    size_id: item.size_id,
                                    size: size?.size_name || '',
                                    extraPrice: parseFloat(size?.extra_price || 0),
                                    unitPrice: unitPrice,
                                    unitPriceDisplay: unitPrice ? unitPrice.toLocaleString(
                                        'id-ID') : '',
                                    qty: parseInt(item.qty || 0)
                                };
                            });

                            sleeveVariants.push({
                                sleeve: sleeveId,
                                basePrice: basePrice,
                                basePriceDisplay: basePrice ? basePrice.toLocaleString('id-ID') :
                                    '',
                                rows: rows,
                                error: '',
                                basePriceError: ''
                            });
                        });

                        this.designVariants.push({
                            name: designName,
                            sleeveVariants: sleeveVariants,
                            error: ''
                        });
                    });
                },

                // ====== DESIGN VARIANT HANDLER ======
                addDesignVariant() {
                    this.designVariants.push({
                        name: '',
                        sleeveVariants: [],
                        error: ''
                    });
                },
                addSleeveVariant(dIndex) {
                    this.designVariants[dIndex].sleeveVariants.push({
                        sleeve: '',
                        basePrice: 0,
                        basePriceDisplay: '',
                        rows: [],
                        error: '',
                        basePriceError: ''
                    });
                },

                // Update unit prices when base price changes
                updateUnitPrices(dIndex, vIndex) {
                    const variant = this.designVariants[dIndex].sleeveVariants[vIndex];
                    const basePrice = parseFloat(variant.basePrice) || 0;

                    variant.rows.forEach(row => {
                        row.unitPrice = basePrice + row.extraPrice;
                        row.unitPriceDisplay = row.unitPrice ? row.unitPrice.toLocaleString('id-ID') : '';
                    });
                },

                // SIZE HANDLER
                toggleSize(size) {
                    let exists = this.selectedSizes.find(s => s.id === size.id);
                    this.selectedSizes = exists ?
                        this.selectedSizes.filter(s => s.id !== size.id) : [...this.selectedSizes, size];
                },
                applySizes() {
                    if (this.selectedDesign !== null && this.selectedVariant !== null) {
                        const variant = this.designVariants[this.selectedDesign].sleeveVariants[this.selectedVariant];
                        const basePrice = parseFloat(variant.basePrice) || 0;

                        this.selectedSizes.forEach(size => {
                            let exists = variant.rows.find(r => r.size_id === size.id);

                            if (!exists) {
                                const extraPrice = parseFloat(size.extra_price) || 0;
                                const unitPrice = basePrice + extraPrice;

                                variant.rows.push({
                                    size_id: size.id,
                                    size: size.size_name,
                                    extraPrice: extraPrice,
                                    unitPrice: unitPrice,
                                    unitPriceDisplay: unitPrice ? unitPrice.toLocaleString('id-ID') : '',
                                    qty: 0
                                });
                            }
                        });

                        this.selectedSizes = [];
                        this.openModal = null;
                    }
                },

                // ADDITIONALS
                addAdditional() {
                    this.additionals.push({
                        service_id: '',
                        price: 0,
                        priceDisplay: '',
                        error: ''
                    });
                },
                removeAdditional(index) {
                    this.additionals.splice(index, 1);
                },

                // CALCULATION
                getSubTotal() {
                    let total = 0;
                    this.designVariants.forEach(design => {
                        design.sleeveVariants.forEach(variant => {
                            variant.rows.forEach(row => {
                                total += (row.unitPrice || 0) * (row.qty || 0);
                            });
                        });
                    });
                    this.additionals.forEach(add => {
                        total += parseInt(add.price || 0);
                    });
                    return total;
                },
                getFinalPrice() {
                    return this.getSubTotal() - (this.discount || 0);
                }
            }
        }
    </script>
@endpush
