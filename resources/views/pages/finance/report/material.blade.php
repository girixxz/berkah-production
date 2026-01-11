@extends('layouts.app')

@section('title', 'Material Report')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-font-base">Material Report</h1>
                <p class="text-sm text-font-muted mt-1">Laporan pembelian dan stok material</p>
            </div>
            <div class="flex items-center space-x-3">
                <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition">
                    Export to Excel
                </button>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-font-base mb-2">Date From</label>
                    <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-font-base mb-2">Date To</label>
                    <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition">
                        Apply Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-font-base">Material Data</h2>
            </div>
            <div class="p-6">
                <div class="text-center py-12 text-font-muted">
                    <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <p class="text-lg font-medium">No data available</p>
                    <p class="text-sm mt-1">Material report data will appear here</p>
                </div>
            </div>
        </div>
    </div>
@endsection
