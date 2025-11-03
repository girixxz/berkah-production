@extends('layouts.app')

@section('title', 'Edit Order')

@section('content')
    @php
        $role = auth()->user()?->role;
        $root = $role === 'owner' ? 'Admin' : 'Menu';
    @endphp

    <x-nav-locate :items="[$root, 'Orders', 'Edit Order']" />

    <form x-data="orderForm()" @customer_id-selected.window="customer_id = $event.detail"
        @sales_id-selected.window="sales_id = $event.detail"
        @product_category_id-selected.window="product_category_id = $event.detail"
        @material_category_id-selected.window="material_category_id = $event.detail"
        @material_texture_id-selected.window="material_texture_id = $event.detail"
        @shipping_id-selected.window="shipping_id = $event.detail" method="POST"
        action="{{ route('admin.orders.update', $order->id) }}"
        class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6 space-y-6 md:space-y-8">
        @csrf
        @method('PUT')

        {{-- ================= Header ================= --}}
        <div class="space-y-6 border-b border-gray-200 pb-6 md:pb-8">
            <h2 class="text-xl font-semibold text-gray-900">Edit Order</h2>
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

                {{-- Order Date (READONLY) --}}
                <div class="space-y-2">
                    <label for="order_date" class="block text-sm font-medium text-gray-600">Order Date</label>
                    <div class="relative">
                        <input id="order_date" name="order_date" type="date" x-model="order_date" readonly
                            class="w-full rounded-md px-3 py-2 text-sm border border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed" />
                        @error('order_date')
                            <p class="absolute left-0 -bottom-5 text-[10px] md:text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Deadline (READONLY) --}}
                <div class="space-y-2">
                    <label for="deadline" class="block text-sm font-medium text-gray-600">Deadline</label>
                    <div class="relative">
                        <input id="deadline" name="deadline" type="date" x-model="deadline" readonly
                            class="w-full rounded-md px-3 py-2 text-sm border border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed" />
                        @error('deadline')
                            <p class="absolute left-0 -bottom-5 text-[10px] md:text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= Customers & Sales (DISABLED) ================= --}}
        <section class="space-y-4 md:space-y-5 border-b border-gray-200 pb-8 md:pb-12">
            <h3 class="text-lg font-semibold text-gray-800">Data Customers & Sales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <div class="relative flex flex-col md:flex-row md:items-start gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24 md:mt-2">Customer</label>

                    <div class="w-full">
                        {{-- Customer Disabled Display --}}
                        <div
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-gray-100 text-gray-600 cursor-not-allowed">
                            {{ $order->customer->customer_name }}
                        </div>
                        <input type="hidden" name="customer_id" value="{{ $order->customer_id }}">
                    </div>
                </div>

                <div class="relative flex flex-col md:flex-row md:items-start gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24 md:mt-2">Sales</label>

                    {{-- Sales Disabled Display --}}
                    <div class="w-full">
                        <div
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm bg-gray-100 text-gray-600 cursor-not-allowed">
                            {{ $order->sales->sales_name }}
                        </div>
                        <input type="hidden" name="sales_id" value="{{ $order->sales_id }}">
                    </div>
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
                            :options="$productCategories" display="product_name" :old="old('product_category_id', $order->product_category_id)" />
                    </div>

                    {{-- Color --}}
                    <div class="relative flex flex-col md:flex-row md:items-center gap-2 md:gap-3">
                        <label class="text-sm text-gray-600 md:w-24">Color</label>
                        <div class="relative w-full">
                            <input type="text" name="product_color" x-model="product_color"
                                value="{{ old('product_color', $order->product_color) }}"
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
                                :options="$materialCategories" display="material_name" :old="old('material_category_id', $order->material_category_id)" />

                            <x-select-form name="material_texture_id" label="Product" placeholder="-- Select Texture --"
                                :options="$materialTextures" display="texture_name" :old="old('material_texture_id', $order->material_texture_id)" />
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
                            placeholder="Write notes here...">{{ old('notes', $order->notes) }}</textarea>
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
                                        <div class="relative w-full md:w-40">
                                            <span
                                                class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm font-medium">
                                                Rp
                                            </span>
                                            <input type="text"
                                                :value="variant.basePrice ? parseInt(variant.basePrice).toLocaleString(
                                                    'id-ID') : ''"
                                                @input="
                                                    let value = $event.target.value.replace(/[^0-9]/g, '');
                                                    variant.basePrice = value ? parseInt(value) : 0;
                                                    $event.target.value = variant.basePrice ? variant.basePrice.toLocaleString('id-ID') : '';
                                                    updateUnitPrices(dIndex, vIndex);
                                                "
                                                placeholder="0"
                                                class="w-full rounded-md border border-gray-300 pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
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
                                    <thead class="bg-gray-50 text-gray-600">
                                        <tr>
                                            <th class="py-2 px-4 text-left w-12">No</th>
                                            <th class="py-2 px-4 text-left min-w-[100px]">Size</th>
                                            <th class="py-2 px-4 text-left min-w-[160px]">Unit Price</th>
                                            <th class="py-2 px-4 text-left min-w-[140px]">QTY</th>
                                            <th class="py-2 px-4 text-left min-w-[140px]">Total Price</th>
                                            <th class="py-2 px-4 text-right w-16">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, rIndex) in variant.rows" :key="rIndex">
                                            <tr class="border-t">
                                                <td class="py-2 px-4" x-text="rIndex+1"></td>
                                                <td class="py-2 px-4">
                                                    <span x-text="row.size"></span>
                                                    <span class="text-xs text-gray-500" x-show="row.extraPrice > 0">
                                                        (+Rp <span x-text="row.extraPrice.toLocaleString('id-ID')"></span>)
                                                    </span>
                                                </td>
                                                <td class="py-2 px-4">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-gray-600 text-sm">Rp</span>
                                                        <input type="text"
                                                            :value="row.unitPrice ? parseInt(row.unitPrice).toLocaleString(
                                                                'id-ID') : ''"
                                                            @input="
                                                                let value = $event.target.value.replace(/[^0-9]/g, '');
                                                                row.unitPrice = value ? parseInt(value) : 0;
                                                                $event.target.value = row.unitPrice ? row.unitPrice.toLocaleString('id-ID') : '';
                                                            "
                                                            :class="row.unitPrice === 0 ? 'border-red-500' : 'border-gray-300'"
                                                            placeholder="0"
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

                                <div class="relative w-full md:w-48">
                                    <span
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm font-medium">
                                        Rp
                                    </span>
                                    <input type="text"
                                        :value="item.price ? parseInt(item.price).toLocaleString('id-ID') : ''"
                                        @input="
                                            let value = $event.target.value.replace(/[^0-9]/g, '');
                                            item.price = value ? parseInt(value) : 0;
                                            $event.target.value = item.price ? item.price.toLocaleString('id-ID') : '';
                                        "
                                        placeholder="0"
                                        class="w-full rounded-md border border-gray-300 pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
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

                {{-- Shipping Type --}}
                <div class="relative flex flex-col gap-2 md:gap-3">
                    <label class="text-sm text-gray-600 md:w-24">Shipping <span class="text-red-500">*</span></label>

                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="shipping_type" value="pickup"
                                {{ old('shipping_type', $order->shipping_type) === 'pickup' ? 'checked' : '' }}
                                class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="text-sm text-gray-700">Pickup</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="shipping_type" value="delivery"
                                {{ old('shipping_type', $order->shipping_type) === 'delivery' ? 'checked' : '' }}
                                class="w-4 h-4 text-primary border-gray-300 focus:ring-primary">
                            <span class="text-sm text-gray-700">Delivery</span>
                        </label>
                    </div>

                    @error('shipping_type')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
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
                        <input type="text" :value="discount ? parseInt(discount).toLocaleString('id-ID') : ''"
                            @input="
                                let value = $event.target.value.replace(/[^0-9]/g, '');
                                discount = value ? parseInt(value) : 0;
                                $event.target.value = discount ? discount.toLocaleString('id-ID') : '';
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
                    Update Order
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
                    this.$dispatch(`${this.fieldName}-selected`, this.selectedId || '');
                },

                select(option) {
                    this.selected = option;
                    this.selectedId = option.id;
                    this.open = false;
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
                priority: '{{ old('priority', $order->priority) }}',
                order_date: '{{ old('order_date', $order->order_date->format('Y-m-d')) }}',
                deadline: '{{ old('deadline', $order->deadline->format('Y-m-d')) }}',
                customer_id: '{{ old('customer_id', $order->customer_id) }}',
                sales_id: '{{ old('sales_id', $order->sales_id) }}',
                product_category_id: '{{ old('product_category_id', $order->product_category_id) }}',
                product_color: '{{ old('product_color', $order->product_color) }}',
                material_category_id: '{{ old('material_category_id', $order->material_category_id) }}',
                material_texture_id: '{{ old('material_texture_id', $order->material_texture_id) }}',
                notes: '{{ old('notes', $order->notes) }}',
                discount: {{ old('discount', $order->discount ?? 0) }},
                shipping_type: '{{ old('shipping_type', $order->shipping_type) }}',

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
                    @if (old('designs'))
                        // If validation error, restore from old input
                        this.restoreFromOldInput();
                    @else
                        // Load existing order data
                        this.loadExistingData();
                    @endif

                    @if (old('additionals'))
                        @foreach (old('additionals', []) as $index => $add)
                            this.additionals.push({
                                service_id: '{{ $add['service_id'] ?? '' }}',
                                price: {{ $add['price'] ?? 0 }},
                                error: ''
                            });
                        @endforeach
                    @else
                        // Load existing additionals
                        @foreach ($order->extraServices as $extra)
                            this.additionals.push({
                                service_id: '{{ $extra->service_id }}',
                                price: {{ $extra->price }},
                                error: ''
                            });
                        @endforeach
                    @endif
                },

                // Load existing order data
                loadExistingData() {
                    const orderItems = @json($order->orderItems()->with(['designVariant', 'size', 'sleeve'])->get());
                    const designMap = {};

                    // Group items by design name
                    orderItems.forEach(item => {
                        const designName = item.design_variant.design_name;
                        if (!designMap[designName]) {
                            designMap[designName] = [];
                        }
                        designMap[designName].push(item);
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
                                return {
                                    size_id: item.size_id,
                                    size: item.size?.size_name || '',
                                    extraPrice: parseFloat(item.size?.extra_price || 0),
                                    unitPrice: parseFloat(item.unit_price),
                                    qty: parseInt(item.qty || 0)
                                };
                            });

                            sleeveVariants.push({
                                sleeve: sleeveId,
                                basePrice: basePrice,
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

                // Restore design variants from old input (for validation errors)
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
                                return {
                                    size_id: item.size_id,
                                    size: size?.size_name || '',
                                    extraPrice: parseFloat(size?.extra_price || 0),
                                    unitPrice: parseFloat(item.unit_price),
                                    qty: parseInt(item.qty || 0)
                                };
                            });

                            sleeveVariants.push({
                                sleeve: sleeveId,
                                basePrice: basePrice,
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
