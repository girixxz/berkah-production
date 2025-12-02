@extends('layouts.app')

@section('title', 'Task')

@section('content')
    @php
        $role = auth()->user()?->role;
        if ($role === 'owner') {
            $root = 'Karyawan';
        } elseif ($role === 'admin') {
            $root = 'Admin';
        } elseif ($role === 'pm') {
            $root = 'PM';
        } else {
            $root = 'Menu';
        }
    @endphp
    <x-nav-locate :items="[$root, 'Task Karyawan']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        showModal: false,
        modalStage: null,
        modalOrders: [],
        showConfirmDone: false,
        selectedOrderStage: null,
        isSubmitting: false,
        async markAsDone(orderStageId, invoiceNo, productName, customer = '', qty = 0, priority = 'normal') {
            this.selectedOrderStage = { 
                id: orderStageId, 
                invoice: invoiceNo, 
                product: productName,
                customer: customer,
                qty: qty,
                priority: priority
            };
            this.showConfirmDone = true;
        },
        async confirmDone() {
            if (!this.selectedOrderStage || this.isSubmitting) return;
    
            this.isSubmitting = true;
    
            try {
                const response = await fetch('{{ route('karyawan.task.mark-done') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        order_stage_id: this.selectedOrderStage.id
                    })
                });
    
                const data = await response.json();
    
                if (data.success) {
                    // Show success toast
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message, type: 'success' }
                    }));
    
                    // Reload page after short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    throw new Error(data.message || 'Failed to mark as done');
                }
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: error.message || 'An error occurred', type: 'error' }
                }));
                this.isSubmitting = false;
            }
        }
    }">

        {{-- Page Title --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Today's Task</h1>
            <p class="text-sm text-gray-500 mt-1">{{ \Carbon\Carbon::today()->format('l, d F Y') }}</p>
        </div>

        {{-- Production Stages Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach ($stagesWithOrders as $stageData)
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden flex flex-col">
                    {{-- Card Header --}}
                    <div class="bg-primary-light p-4 border-b border-gray-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <h3 class="text-gray-900 font-semibold text-base">{{ $stageData['stage']->stage_name }}</h3>
                                <span class="text-gray-600 text-sm">({{ $stageData['total_count'] }} {{ Str::plural('Task', $stageData['total_count']) }})</span>
                            </div>
                            <div class="px-3 py-1 bg-primary text-white text-sm font-medium rounded-md">
                                {{ number_format($stageData['total_pcs']) }} Pcs
                            </div>
                        </div>
                    </div>

                    {{-- Card Body - Order Bubbles --}}
                    <div class="p-4 min-h-[200px] flex flex-col flex-1">
                        @if ($stageData['total_count'] > 0)
                            <div class="space-y-2 flex-1">
                                @foreach ($stageData['order_stages']->take(5) as $orderStage)
                                    @php
                                        $isHighPriority = strtolower($orderStage->order->priority ?? '') === 'high';
                                        $isLast = $loop->iteration >= 4; // Last 2 items, dropdown goes up
                                        
                                        // Set colors based on priority (same as calendar)
                                        if ($isHighPriority) {
                                            $bgClass = 'bg-red-500';
                                            $borderClass = 'border-red-600';
                                            $textClass = 'text-white';
                                            $separatorClass = 'text-white opacity-70';
                                        } else {
                                            $bgClass = 'bg-[#eddfad]';
                                            $borderClass = 'border-[#d4c973]';
                                            $textClass = 'text-gray-900';
                                            $separatorClass = 'text-gray-600';
                                        }
                                    @endphp
                                    <div class="px-3 py-2 rounded-lg border {{ $bgClass }} {{ $borderClass }} text-xs font-medium"
                                        x-data="{ showDropdown: false }">
                                        <div class="flex items-center justify-between gap-2">
                                            {{-- Content: Nama • Product • QTY • HIGH (horizontal row) --}}
                                            <div class="flex items-center gap-2 flex-1 min-w-0 {{ $textClass }}">
                                                <span class="font-semibold truncate">
                                                    {{ $orderStage->order->customer->customer_name ?? 'N/A' }}
                                                </span>
                                                <span class="{{ $separatorClass }}">•</span>
                                                <span class="truncate">
                                                    {{ $orderStage->order->productCategory->product_name ?? 'N/A' }}
                                                </span>
                                                <span class="{{ $separatorClass }}">•</span>
                                                <span class="font-medium">
                                                    {{ $orderStage->order->total_qty ?? 0 }}
                                                </span>
                                                @if ($isHighPriority)
                                                    <span class="{{ $separatorClass }}">•</span>
                                                    <span class="font-bold italic">HIGH</span>
                                                @endif
                                            </div>
                                            {{-- Three Dot Button --}}
                                            <div class="flex-shrink-0 relative">
                                                @if ($orderStage->status !== 'done')
                                                    <button type="button" @click="showDropdown = !showDropdown"
                                                        class="p-1 hover:bg-gray-200 rounded transition-colors cursor-pointer"
                                                        title="Actions">
                                                        <svg class="w-4 h-4 text-gray-600" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path
                                                                d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                        </svg>
                                                    </button>

                                                    {{-- Dropdown Menu --}}
                                                    <div x-show="showDropdown" @click.away="showDropdown = false" x-cloak
                                                        class="absolute right-0 {{ $isLast ? 'bottom-full mb-1' : 'top-full mt-1' }} w-40 bg-white rounded-lg shadow-lg border border-gray-200 z-[9999] py-1">
                                                        {{-- View Detail --}}
                                                        <a href="{{ route('karyawan.task.work-order', ['order' => $orderStage->order->id]) }}"
                                                            @click="showDropdown = false"
                                                            class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2 cursor-pointer">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                            View Detail
                                                        </a>
                                                        {{-- Mark as Done --}}
                                                        <button type="button"
                                                            @click="showDropdown = false; markAsDone({{ $orderStage->id }}, '{{ $orderStage->order->invoice->invoice_no ?? 'N/A' }}', '{{ $orderStage->order->productCategory->product_name ?? 'N/A' }}', '{{ $orderStage->order->customer->customer_name ?? 'N/A' }}', {{ $orderStage->order->total_qty ?? 0 }}, '{{ strtolower($orderStage->order->priority ?? 'normal') }}')"
                                                            class="w-full text-left px-4 py-2 text-xs text-green-700 hover:bg-green-50 flex items-center gap-2 cursor-pointer">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Done
                                                        </button>
                                                    </div>
                                                @else
                                                    <svg class="w-4 h-4 text-green-500" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            {{-- View All Button - Always at bottom --}}
                            <div class="mt-auto pt-3">
                                <button type="button"
                                    @click="showModal = true; 
                                            modalStage = '{{ $stageData['stage']->stage_name }}'; 
                                            modalOrders = {{ $stageData['order_stages']->map(function ($os) {
                                                    $isHigh = strtolower($os->order->priority ?? '') === 'high';
                                                    return [
                                                        'id' => $os->id,
                                                        'invoice' => $os->order->invoice->invoice_no ?? 'N/A',
                                                        'product' => $os->order->productCategory->product_name ?? 'N/A',
                                                        'customer' => $os->order->customer->customer_name ?? 'N/A',
                                                        'qty' => $os->order->total_qty ?? 0,
                                                        'priority' => $isHigh ? 'high' : 'normal',
                                                        'status' => $os->status,
                                                        'deadline' => $os->deadline ? $os->deadline->format('d M Y H:i') : 'N/A',
                                                    ];
                                                })->toJson() }}"
                                    class="w-full text-center text-sm font-medium text-primary hover:text-primary-dark transition-colors cursor-pointer border-t border-gray-200 pt-3">
                                    @if ($stageData['remaining_count'] > 0)
                                        View All ({{ $stageData['remaining_count'] }}+ More)
                                    @else
                                        View All
                                    @endif
                                </button>
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-full py-8">
                                <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-gray-400 text-sm">No tasks today</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Modal - Show All Orders --}}
        <div x-show="showModal" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 overflow-y-auto bg-gray-500/50 backdrop-blur-sm">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div @click.away="showModal = false" class="bg-white rounded-xl shadow-lg w-full max-w-2xl">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between p-5 border-b border-gray-200">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900" x-text="modalStage"></h3>
                            <p class="text-sm text-gray-500 mt-1">All tasks for today</p>
                        </div>
                        <button @click="showModal = false"
                            class="text-gray-400 hover:text-gray-600 cursor-pointer transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-5 max-h-[60vh] overflow-y-auto">
                        <div class="space-y-3">
                            <template x-for="(order, index) in modalOrders" :key="index">
                                <div class="px-4 py-3 rounded-lg border"
                                    :style="order.priority !== 'high' ? 'background-color: #eddfad; border-color: #d4c973;' : ''"
                                    :class="{
                                        'bg-red-500 border-red-600': order.priority === 'high'
                                    }"
                                    x-data="{ showDropdown: false }">
                                    <div class="flex items-center justify-between gap-3">
                                        {{-- Content: Nama • Product • QTY • HIGH (horizontal row) --}}
                                        <div class="flex items-center gap-2 flex-1 min-w-0 text-xs font-medium"
                                            :class="order.priority === 'high' ? 'text-white' : 'text-gray-900'">
                                            <span class="font-semibold truncate" x-text="order.customer"></span>
                                            <span :class="order.priority === 'high' ? 'text-white opacity-70' : 'text-gray-600'">•</span>
                                            <span class="truncate" x-text="order.product"></span>
                                            <span :class="order.priority === 'high' ? 'text-white opacity-70' : 'text-gray-600'">•</span>
                                            <span class="font-medium" x-text="order.qty || 0"></span>
                                            <template x-if="order.priority === 'high'">
                                                <span>
                                                    <span class="text-white opacity-70">•</span>
                                                    <span class="font-bold italic">HIGH</span>
                                                </span>
                                            </template>
                                        </div>
                                        {{-- Three Dot Button --}}
                                        <div class="flex-shrink-0 relative">
                                            <template x-if="order.status !== 'done'">
                                                <button type="button" @click="showDropdown = !showDropdown"
                                                    class="p-1 hover:bg-gray-200 rounded transition-colors cursor-pointer"
                                                    title="Actions">
                                                    <svg class="w-4 h-4 text-gray-600" fill="currentColor"
                                                        viewBox="0 0 20 20">
                                                        <path
                                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    </svg>
                                                </button>
                                            </template>
                                            
                                            {{-- Dropdown Menu - Auto adjust position based on index --}}
                                            <div x-show="showDropdown" @click.away="showDropdown = false" x-cloak
                                                class="absolute right-0 w-40 bg-white rounded-lg shadow-lg border border-gray-200 z-[9999] py-1"
                                                :class="index >= modalOrders.length - 3 ? 'bottom-full mb-1' : 'top-full mt-1'">
                                                {{-- View Detail --}}
                                                <a href="{{ route('karyawan.task.work-order', ['order' => $orderStage->order->id]) }}"
                                                    @click="showDropdown = false"
                                                    class="w-full text-left px-4 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2 cursor-pointer">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View Detail
                                                </a>
                                                {{-- Mark as Done --}}
                                                <button type="button"
                                                    @click="showDropdown = false; markAsDone(order.id, order.invoice, order.product, order.customer, order.qty, order.priority)"
                                                    class="w-full text-left px-4 py-2 text-xs text-green-700 hover:bg-green-50 flex items-center gap-2 cursor-pointer">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    Done
                                                </button>
                                            </div>
                                            
                                            {{-- Done checkmark --}}
                                            <template x-if="order.status === 'done'">
                                                <svg class="w-4 h-4 text-green-500" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex justify-end gap-3 p-5 border-t border-gray-200">
                        <button @click="showModal = false"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium cursor-pointer transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Confirmation Modal - Mark as Done --}}
        <div x-show="showConfirmDone" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 overflow-y-auto bg-gray-500/50 backdrop-blur-sm">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div @click.away="showConfirmDone = false; isSubmitting = false"
                    class="bg-white rounded-xl shadow-lg w-full max-w-md">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-center p-6 border-b border-gray-200">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6 text-center">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Mark Task as Done?</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            You are about to mark this task as completed:
                        </p>
                        {{-- Bubble sama seperti di page awal --}}
                        <div class="px-3 py-2 rounded-lg border text-xs font-medium mb-4"
                            x-data="{ isHighPriority: selectedOrderStage?.priority === 'high' }"
                            :style="!isHighPriority ? 'background-color: #eddfad; border-color: #d4c973;' : ''"
                            :class="{
                                'bg-red-500 border-red-600': isHighPriority
                            }">
                            <div class="flex items-center gap-2 justify-center"
                                :class="isHighPriority ? 'text-white' : 'text-gray-900'">
                                <span class="font-semibold" x-text="selectedOrderStage?.customer"></span>
                                <span :class="isHighPriority ? 'text-white opacity-70' : 'text-gray-600'">•</span>
                                <span x-text="selectedOrderStage?.product"></span>
                                <span :class="isHighPriority ? 'text-white opacity-70' : 'text-gray-600'">•</span>
                                <span class="font-medium" x-text="selectedOrderStage?.qty || 0"></span>
                                <template x-if="isHighPriority">
                                    <span>
                                        <span class="text-white opacity-70">•</span>
                                        <span class="font-bold italic">HIGH</span>
                                    </span>
                                </template>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">
                            This action will update the stage status in PM Manage Task.
                        </p>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex gap-3 p-6 border-t border-gray-200">
                        <button type="button" @click="showConfirmDone = false; isSubmitting = false"
                            :disabled="isSubmitting"
                            class="flex-1 px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium cursor-pointer transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Cancel
                        </button>
                        <button type="button" @click="confirmDone()" :disabled="isSubmitting"
                            class="flex-1 px-4 py-2 rounded-md bg-green-600 hover:bg-green-700 text-white text-sm font-medium cursor-pointer transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isSubmitting">Yes, Mark as Done</span>
                            <span x-show="isSubmitting">Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
