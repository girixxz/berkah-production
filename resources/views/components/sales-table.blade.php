<table class="min-w-[450px] w-full text-sm">
    <thead class="sticky top-0 bg-primary-light text-font-base z-10">
        <tr>
            <th class="py-2 px-4 text-left rounded-l-md">No</th>
            <th class="py-2 px-4 text-left">Sales Name</th>
            <th class="py-2 px-4 text-left">Phone</th>
            <th class="py-2 px-4 text-right">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($sales as $sale)
            <tr class="border-t border-gray-200">
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
                    }" x-init="$watch('open', value => {
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

                        <div x-show="open" @click.away="open = false" x-transition :style="dropdownStyle"
                            class="rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[9999]">
                            <div class="py-1">
                                <button
                                    @click="editSales = {{ $sale->toJson() }}; openModal = 'editSales'; open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>

                                <form action="{{ route('owner.manage-data.users-sales.sales.destroy', $sale) }}"
                                    method="POST" class="inline w-full"
                                    onsubmit="return confirm('Are you sure you want to delete this sales?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                    No Sales found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
