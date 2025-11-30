@props(['name', 'placeholder' => '-- Select --', 'options' => [], 'value' => 'value', 'display' => 'name', 'old' => null])

<div x-data="{
    open: false,
    options: @js($options),
    selected: null,
    selectedValue: '{{ $old }}',
    
    init() {
        if (this.selectedValue) {
            this.selected = this.options.find(o => String(o['{{ $value }}']) === String(this.selectedValue)) || null;
        }
    },
    
    select(option) {
        this.selected = option;
        this.selectedValue = option['{{ $value }}'];
        this.open = false;
    }
}" class="relative w-full">
    {{-- Trigger --}}
    <button type="button" @click="open = !open"
        :class="(errors && errors['{{ $name }}']) || {{ $errors->has($name) ? 'true' : 'false' }} ? 'border-red-500' : 'border-gray-200'"
        class="w-full flex justify-between items-center rounded-md border px-3 py-2 text-sm text-gray-700 bg-white
               focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
        <span x-text="selected ? selected['{{ $display }}'] : '{{ $placeholder }}'"
            :class="!selected ? 'text-gray-400' : 'text-gray-900'"></span>
        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Hidden input --}}
    <input type="hidden" name="{{ $name }}" x-model="selectedValue">

    {{-- Dropdown --}}
    <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
        <ul class="max-h-60 overflow-y-auto py-1">
            <template x-for="option in options" :key="option['{{ $value }}']">
                <li @click="select(option)"
                    class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                    :class="{ 'bg-primary/10 font-medium text-primary': selected && selected['{{ $value }}'] === option['{{ $value }}'] }">
                    <span x-text="option['{{ $display }}']"></span>
                </li>
            </template>
        </ul>
    </div>

    {{-- Error message dari Laravel --}}
    @error($name)
        <span class="absolute left-0 -bottom-5 text-[12px] text-red-600">
            {{ $message }}
        </span>
    @enderror
</div>
