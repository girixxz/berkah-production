@extends('layouts.app')
@section('title', 'Manage Employees')
@section('content')
    <x-nav-locate :items="['Menu', 'Manage Data', 'Employees']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        openModal: '{{ session('openModal') }}',
        editEmployee: {},
        searchEmployee: '',
        editEmployeeErrors: {},
    
        validateEditEmployee() {
            this.editEmployeeErrors = {};
            let isValid = true;
            
            console.log('Validating employee:', this.editEmployee);
    
            // Full Name - Required
            if (!this.editEmployee.fullname || this.editEmployee.fullname.trim() === '') {
                this.editEmployeeErrors.fullname = 'Full Name is required';
                isValid = false;
            } else if (this.editEmployee.fullname.length > 255) {
                this.editEmployeeErrors.fullname = 'Full Name must not exceed 255 characters';
                isValid = false;
            }
    
            // Birth Date - Required (Day, Month, Year)
            if (!this.editEmployee.birth_day) {
                this.editEmployeeErrors.birth_day = 'Day is required';
                isValid = false;
            } else if (this.editEmployee.birth_day < 1 || this.editEmployee.birth_day > 31) {
                this.editEmployeeErrors.birth_day = 'Day must be between 1 and 31';
                isValid = false;
            }
            
            if (!this.editEmployee.birth_month) {
                this.editEmployeeErrors.birth_month = 'Month is required';
                isValid = false;
            }
            
            if (!this.editEmployee.birth_year) {
                this.editEmployeeErrors.birth_year = 'Year is required';
                isValid = false;
            } else if (this.editEmployee.birth_year < 1900 || this.editEmployee.birth_year > {{ date('Y') }}) {
                this.editEmployeeErrors.birth_year = 'Year must be between 1900 and {{ date('Y') }}';
                isValid = false;
            }
    
            // Work Date Month - Required
            if (!this.editEmployee.work_month) {
                this.editEmployeeErrors.work_month = 'Work Month is required';
                isValid = false;
            }
            
            // Work Date Year - Required
            if (!this.editEmployee.work_year) {
                this.editEmployeeErrors.work_year = 'Work Year is required';
                isValid = false;
            } else if (this.editEmployee.work_year < 1900 || this.editEmployee.work_year > 2100) {
                this.editEmployeeErrors.work_year = 'Year must be between 1900 and 2100';
                isValid = false;
            }
    
            // Dress Size - Required
            if (!this.editEmployee.dress_size) {
                this.editEmployeeErrors.dress_size = 'Dress Size is required';
                isValid = false;
            }
    
            // Salary System - Required
            if (!this.editEmployee.salary_system || this.editEmployee.salary_system.trim() === '') {
                this.editEmployeeErrors.salary_system = 'Salary System is required';
                isValid = false;
            }
            
            // Salary Cycle - Required
            if (!this.editEmployee.salary_cycle) {
                this.editEmployeeErrors.salary_cycle = 'Salary Cycle is required';
                isValid = false;
            } else {
                const cycle = parseInt(this.editEmployee.salary_cycle);
                if (isNaN(cycle) || cycle < 1 || cycle > 31) {
                    this.editEmployeeErrors.salary_cycle = 'Cycle must be between 1 and 31';
                    isValid = false;
                }
            }
    
            // Address - Required
            if (!this.editEmployee.address || this.editEmployee.address.trim() === '') {
                this.editEmployeeErrors.address = 'Address is required';
                isValid = false;
            }
            
            console.log('Validation errors:', this.editEmployeeErrors);
            console.log('Is valid:', isValid);
    
            return isValid;
        },
    
        init() {
            this.$watch('openModal', value => {
                if (value === 'editEmployee' && this.editEmployee) {
                    this.editEmployeeErrors = {};
                }
            });
        },
        
        setEditEmployee(employee) {
            this.editEmployee = employee;
            
            // Split birth_date into birth_day, birth_month, birth_year (format: YYYY-MM-DD)
            if (employee.birth_date) {
                const birthParts = employee.birth_date.split('-');
                this.editEmployee.birth_year = birthParts[0] || '';
                const monthNum = parseInt(birthParts[1]);
                const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                this.editEmployee.birth_month = months[monthNum - 1] || '';
                this.editEmployee.birth_day = parseInt(birthParts[2]) || '';
            } else {
                this.editEmployee.birth_day = '';
                this.editEmployee.birth_month = '';
                this.editEmployee.birth_year = '';
            }
            
            // Split work_date into work_month and work_year
            if (employee.work_date) {
                const parts = employee.work_date.split(' ');
                this.editEmployee.work_month = parts[0] || '';
                this.editEmployee.work_year = parts[1] || '';
            } else {
                this.editEmployee.work_month = '';
                this.editEmployee.work_year = '';
            }
        }
    }">

        {{-- ===================== EMPLOYEES ===================== --}}
        <section id="employees-section" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <h2 class="text-xl font-semibold text-gray-900">Employees</h2>

                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto min-w-0">
                    {{-- Search --}}
                    <div class="flex-1">
                        <div class="relative">
                            <x-icons.search />
                            <input type="text" x-model="searchEmployee" placeholder="Search Employee"
                                class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="mt-5 overflow-x-auto">
                <div class="max-h-178 overflow-y-auto">
                    <table class="min-w-[450px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">Fullname</th>
                                <th class="py-2 px-4 text-left">Birth Date</th>
                                <th class="py-2 px-4 text-left">Work Date</th>
                                <th class="py-2 px-4 text-left">Dress Size</th>
                                <th class="py-2 px-4 text-left">Salary System</th>
                                <th class="py-2 px-4 text-left">Address</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody id="employees-tbody">
                            @forelse ($employees as $employee)
                                <tr class="border-t border-gray-200"
                                    x-show="
                                        {{ Js::from(strtolower($employee->fullname . ' ' . ($employee->address ?? ''))) }}
                                        .includes(searchEmployee.toLowerCase())
                                    ">
                                    <td class="py-2 px-4">
                                        {{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
                                    </td>
                                    <td class="py-2 px-4">{{ $employee->fullname }}</td>
                                    <td class="py-2 px-4">
                                        @if($employee->birth_date)
                                            {{ \Carbon\Carbon::parse($employee->birth_date)->format('d M Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-2 px-4">{{ $employee->work_date ?? '-' }}</td>
                                    <td class="py-2 px-4">{{ $employee->dress_size ?? '-' }}</td>
                                    <td class="py-2 px-4">
                                        @if($employee->salary_system && $employee->salary_cycle)
                                            {{ $employee->salary_system }} ({{ $employee->salary_cycle }}x)
                                        @else
                                            {{ $employee->salary_system ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="py-2 px-4">{{ $employee->address ?? '-' }}</td>

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
                                        
                                                // Position fixed dropdown
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
                                            {{-- Tombol Titik 3 Horizontal --}}
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>

                                            {{-- Dropdown Menu with Fixed Position --}}
                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[9999]">
                                                <div class="py-1">
                                                    {{-- Edit --}}
                                                    <button
                                                        @click="setEditEmployee({{ $employee->toJson() }}); openModal = 'editEmployee'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Employees found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="mt-4" id="employees-pagination-container">
                <x-custom-pagination :paginator="$employees" />
            </div>
        </section>

        {{-- ===================== MODALS ===================== --}}
        {{-- ========== Edit Employee Modal ========== --}}
        <div x-show="openModal === 'editEmployee'" x-cloak 
            x-init="
                @if (session('openModal') === 'editEmployee' && session('editEmployeeId')) 
                    setEditEmployee({{ \App\Models\User::find(session('editEmployeeId'))->toJson() }});
                @endif
            "
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 sticky top-0 bg-white z-10 rounded-t-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Employee</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>

                <form :action="`{{ route('owner.manage-data.employees.index') }}/${editEmployee.id}`" method="POST"
                    @submit.prevent="
                        if (validateEditEmployee()) {
                            $el.submit();
                        }
                    "
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Fullname --}}
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="fullname" x-model="editEmployee.fullname" 
                            @blur="validateEditEmployee()"
                            :class="editEmployeeErrors.fullname || {{ $errors->editEmployee->has('fullname') ? 'true' : 'false' }} ? 
                                'border-red-500 focus:border-red-500 focus:ring-red-200' : 
                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                            required maxlength="255"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm pr-10 border focus:outline-none focus:ring-2 text-gray-700">
                        
                        @if ($errors->editEmployee->has('fullname'))
                            <span class="absolute right-3 top-[42px] -translate-y-1/2 text-red-500 pointer-events-none">
                                <x-icons.danger />
                            </span>
                        @endif

                        <p x-show="editEmployeeErrors.fullname" x-text="editEmployeeErrors.fullname"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('fullname', 'editEmployee')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Birth Date (Day, Month, Year) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Birth Date <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-3 gap-3">
                            {{-- Day Select --}}
                            <div x-data="{
                                open: false,
                                days: Array.from({length: 31}, (_, i) => i + 1)
                            }" class="relative w-full">
                                <button type="button" @click="open = !open"
                                    :class="editEmployeeErrors.birth_day ? 'border-red-500' : 'border-gray-200'"
                                    class="w-full flex justify-between items-center rounded-md border px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                    <span x-text="editEmployee.birth_day || 'Day'"
                                        :class="!editEmployee.birth_day ? 'text-gray-400' : 'text-gray-900'"></span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <input type="hidden" name="birth_day" x-model="editEmployee.birth_day">
                                <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="day in days" :key="day">
                                            <li @click="editEmployee.birth_day = day; open = false"
                                                class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                                :class="{ 'bg-primary/10 font-medium text-primary': editEmployee.birth_day == day }">
                                                <span x-text="day"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                                <p x-show="editEmployeeErrors.birth_day" x-text="editEmployeeErrors.birth_day"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- Month Select --}}
                            <div x-data="{
                                open: false,
                                months: [
                                    'January', 'February', 'March', 'April', 'May', 'June',
                                    'July', 'August', 'September', 'October', 'November', 'December'
                                ]
                            }" class="relative w-full">
                                <button type="button" @click="open = !open"
                                    :class="editEmployeeErrors.birth_month ? 'border-red-500' : 'border-gray-200'"
                                    class="w-full flex justify-between items-center rounded-md border px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                    <span x-text="editEmployee.birth_month || 'Month'"
                                        :class="!editEmployee.birth_month ? 'text-gray-400' : 'text-gray-900'"></span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <input type="hidden" name="birth_month" x-model="editEmployee.birth_month">
                                <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="month in months" :key="month">
                                            <li @click="editEmployee.birth_month = month; open = false"
                                                class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                                :class="{ 'bg-primary/10 font-medium text-primary': editEmployee.birth_month === month }">
                                                <span x-text="month"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                                <p x-show="editEmployeeErrors.birth_month" x-text="editEmployeeErrors.birth_month"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>

                            {{-- Year Select --}}
                            <div x-data="{
                                open: false,
                                years: Array.from({length: {{ date('Y') }} - 1900 + 1}, (_, i) => {{ date('Y') }} - i)
                            }" class="relative w-full">
                                <button type="button" @click="open = !open"
                                    :class="editEmployeeErrors.birth_year ? 'border-red-500' : 'border-gray-200'"
                                    class="w-full flex justify-between items-center rounded-md border px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                    <span x-text="editEmployee.birth_year || 'Year'"
                                        :class="!editEmployee.birth_year ? 'text-gray-400' : 'text-gray-900'"></span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <input type="hidden" name="birth_year" x-model="editEmployee.birth_year">
                                <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="year in years" :key="year">
                                            <li @click="editEmployee.birth_year = year; open = false; validateEditEmployee()"
                                                class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                                :class="{ 'bg-primary/10 font-medium text-primary': editEmployee.birth_year == year }">
                                                <span x-text="year"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                                <p x-show="editEmployeeErrors.birth_year" x-text="editEmployeeErrors.birth_year"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>
                        </div>
                        @error('birth_day', 'editEmployee')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('birth_month', 'editEmployee')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('birth_year', 'editEmployee')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Work Date (Month & Year) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Work Date <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            {{-- Month Select --}}
                            <div x-data="{
                                open: false,
                                months: [
                                    'January', 'February', 'March', 'April', 'May', 'June',
                                    'July', 'August', 'September', 'October', 'November', 'December'
                                ]
                            }" class="relative w-full">
                                <button type="button" @click="open = !open"
                                    :class="editEmployeeErrors.work_month ? 'border-red-500' : 'border-gray-200'"
                                    class="w-full flex justify-between items-center rounded-md border px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                    <span x-text="editEmployee.work_month || '-- Select --'"
                                        :class="!editEmployee.work_month ? 'text-gray-400' : 'text-gray-900'"></span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <input type="hidden" name="work_month" x-model="editEmployee.work_month">
                                <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="month in months" :key="month">
                                            <li @click="editEmployee.work_month = month; open = false"
                                                class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                                :class="{ 'bg-primary/10 font-medium text-primary': editEmployee.work_month === month }">
                                                <span x-text="month"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                                
                                <p x-show="editEmployeeErrors.work_month" x-text="editEmployeeErrors.work_month"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>


                            {{-- Year Select --}}
                            <div x-data="{
                                open: false,
                                years: Array.from({length: {{ date('Y') }} - 1900 + 1}, (_, i) => {{ date('Y') }} - i)
                            }" class="relative w-full">
                                <button type="button" @click="open = !open"
                                    :class="editEmployeeErrors.work_year ? 'border-red-500' : 'border-gray-200'"
                                    class="w-full flex justify-between items-center rounded-md border px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                    <span x-text="editEmployee.work_year || '-- Select --'"
                                        :class="!editEmployee.work_year ? 'text-gray-400' : 'text-gray-900'"></span>
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <input type="hidden" name="work_year" x-model="editEmployee.work_year">
                                <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                    <ul class="max-h-60 overflow-y-auto py-1">
                                        <template x-for="year in years" :key="year">
                                            <li @click="editEmployee.work_year = year; open = false; validateEditEmployee()"
                                                class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                                :class="{ 'bg-primary/10 font-medium text-primary': editEmployee.work_year == year }">
                                                <span x-text="year"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                                <p x-show="editEmployeeErrors.work_year" x-text="editEmployeeErrors.work_year"
                                    class="mt-1 text-sm text-red-600"></p>
                            </div>
                        </div>
                        @error('work_month', 'editEmployee')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('work_year', 'editEmployee')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Dress Size --}}
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dress Size <span class="text-red-500">*</span></label>
                        <div x-data="{
                            open: false,
                            sizes: ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']
                        }" class="relative w-full">
                            <button type="button" @click="open = !open"
                                :class="editEmployeeErrors.dress_size ? 'border-red-500' : 'border-gray-200'"
                                class="w-full flex justify-between items-center rounded-md border px-3 py-2 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                <span x-text="editEmployee.dress_size || '-- Select --'"
                                    :class="!editEmployee.dress_size ? 'text-gray-400' : 'text-gray-900'"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <input type="hidden" name="dress_size" x-model="editEmployee.dress_size">
                            <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                <ul class="max-h-60 overflow-y-auto py-1">
                                    <template x-for="size in sizes" :key="size">
                                        <li @click="editEmployee.dress_size = size; open = false"
                                            class="px-4 py-2 cursor-pointer text-sm hover:bg-primary/5 transition-colors"
                                            :class="{ 'bg-primary/10 font-medium text-primary': editEmployee.dress_size === size }">
                                            <span x-text="size"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                        
                        <p x-show="editEmployeeErrors.dress_size" x-text="editEmployeeErrors.dress_size"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('dress_size', 'editEmployee')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Salary System (System & Cycle) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Salary System <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="relative">
                                <input type="text" name="salary_system" x-model="editEmployee.salary_system" 
                                    @blur="validateEditEmployee()"
                                    :class="editEmployeeErrors.salary_system ? 'border-red-500' : 'border-gray-200'"
                                    placeholder="Daily, Monthly, etc"
                                    class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-gray-700">
                                <p x-show="editEmployeeErrors.salary_system" x-text="editEmployeeErrors.salary_system"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('salary_system', 'editEmployee')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="relative">
                                <input type="number" name="salary_cycle" x-model="editEmployee.salary_cycle" 
                                    @blur="validateEditEmployee()"
                                    :class="editEmployeeErrors.salary_cycle ? 'border-red-500' : 'border-gray-200'"
                                    placeholder="1x, 2x, etc" min="1" max="31"
                                    class="w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-gray-700">
                                <p x-show="editEmployeeErrors.salary_cycle" x-text="editEmployeeErrors.salary_cycle"
                                    class="mt-1 text-sm text-red-600"></p>
                                @error('salary_cycle', 'editEmployee')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700">Address <span class="text-red-500">*</span></label>
                        <textarea name="address" x-model="editEmployee.address" rows="3"
                            @blur="validateEditEmployee()"
                            :class="editEmployeeErrors.address ? 'border-red-500' : 'border-gray-200'"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-gray-700"
                            placeholder="Enter full address..."></textarea>
                        
                        <p x-show="editEmployeeErrors.address" x-text="editEmployeeErrors.address"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('address', 'editEmployee')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 sticky bottom-0 bg-white pb-2">
                        <button type="button" @click="openModal=null"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark cursor-pointer">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
