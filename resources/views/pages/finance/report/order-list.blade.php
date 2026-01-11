@extends('layouts.app')

@section('title', 'Order List Report')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-font-base">Order List Report</h1>
                <p class="text-sm text-font-muted mt-1">Laporan daftar pesanan dan revenue</p>
            </div>
            <div class="flex items-center space-x-3">
                <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition">
                    Export to Excel
                </button>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-font-base mb-2">Date From</label>
                    <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-font-base mb-2">Date To</label>
                    <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-font-base mb-2">Status</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="wip">Work In Progress</option>
                        <option value="finished">Finished</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
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
                <h2 class="text-lg font-semibold text-font-base">Report Data</h2>
            </div>
            <div class="p-6">
                <div class="text-center py-12 text-font-muted">
                    <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-lg font-medium">No data available</p>
                    <p class="text-sm mt-1">Report data will appear here</p>
                </div>
            </div>
        </div>
    </div>
@endsection
