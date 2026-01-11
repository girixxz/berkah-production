@extends('layouts.app')

@section('title', 'Support Partner Report')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-font-base">Support Partner Report</h1>
                <p class="text-sm text-font-muted mt-1">Laporan biaya mitra pendukung (vendor, supplier)</p>
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
                <h2 class="text-lg font-semibold text-font-base">Support Partner Data</h2>
            </div>
            <div class="p-6">
                <div class="text-center py-12 text-font-muted">
                    <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="text-lg font-medium">No data available</p>
                    <p class="text-sm mt-1">Support partner report data will appear here</p>
                </div>
            </div>
        </div>
    </div>
@endsection
