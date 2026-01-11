@extends('layouts.app')

@section('title', 'Internal Transfer')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-font-base">Internal Transfer</h1>
                <p class="text-sm text-font-muted mt-1">Transfer dana antar akun internal perusahaan</p>
            </div>
            <div class="flex items-center space-x-3">
                <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition">
                    + New Transfer
                </button>
            </div>
        </div>

        <!-- Transfer Form -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-font-base mb-6">Create Transfer</h2>
            <form class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-font-base mb-2">From Account</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Select Account</option>
                            <option value="main">Main Account</option>
                            <option value="operational">Operational Account</option>
                            <option value="savings">Savings Account</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-font-base mb-2">To Account</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Select Account</option>
                            <option value="main">Main Account</option>
                            <option value="operational">Operational Account</option>
                            <option value="savings">Savings Account</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-font-base mb-2">Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-font-muted">Rp</span>
                        <input type="number" class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="0">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-font-base mb-2">Description</label>
                    <textarea rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Transfer description..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-font-base mb-2">Transfer Date</label>
                    <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" class="px-6 py-2 border border-gray-300 text-font-base rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition">
                        Process Transfer
                    </button>
                </div>
            </form>
        </div>

        <!-- Transfer History -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-font-base">Transfer History</h2>
            </div>
            <div class="p-6">
                <div class="text-center py-12 text-font-muted">
                    <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <p class="text-lg font-medium">No transfer history</p>
                    <p class="text-sm mt-1">Transfer records will appear here</p>
                </div>
            </div>
        </div>
    </div>
@endsection
