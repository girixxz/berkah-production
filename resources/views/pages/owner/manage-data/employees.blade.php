@extends('layouts.app')
@section('title', 'Manage Employees')
@section('content')
    <x-nav-locate :items="['Menu', 'Manage Data', 'Employees']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        openModal: '{{ session('openModal') }}',
        editEmployee: {},
        viewEmployee: {},
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

            // Scroll to section after redirect from update
            @if (session('scrollToSection'))
                const section = document.getElementById('{{ session('scrollToSection') }}');
                if (section) {
                    setTimeout(() => {
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
            @endif
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
        },

        setViewEmployee(employee) {
            this.viewEmployee = employee;
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
                                <th class="py-2 px-4 text-left rounded-l-md w-16">No</th>
                                <th class="py-2 px-4 text-left w-40">Fullname</th>
                                <th class="py-2 px-4 text-left w-32">Birth Date</th>
                                <th class="py-2 px-4 text-left w-32">Work Date</th>
                                <th class="py-2 px-4 text-left w-24">Dress Size</th>
                                <th class="py-2 px-4 text-left w-36">Salary System</th>
                                <th class="py-2 px-4 text-left w-64">Address</th>
                                <th class="py-2 px-4 text-right rounded-r-md w-20">Action</th>
                            </tr>
                        </thead>
                        <tbody id="employees-tbody" x-data="{
                            get hasResults() {
                                if (searchEmployee.trim() === '') return true;
                                const search = searchEmployee.toLowerCase();
                                return {{ Js::from($allEmployees->map(fn($e) => strtolower($e->fullname . ' ' . ($e->address ?? '')))) }}
                                    .some(text => text.includes(search));
                            }
                        }">
                            @forelse ($employees as $employee)
                                <tr class="border-t border-gray-200"
                                    x-show="searchEmployee.trim() === ''">
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
                                    <td class="py-2 px-4 max-w-64">
                                        <div class="truncate" title="{{ $employee->address ?? '-' }}">
                                            {{ $employee->address ?? '-' }}
                                        </div>
                                    </td>

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
                                                    {{-- View Detail --}}
                                                    <button
                                                        @click="setViewEmployee({{ $employee->toJson() }}); openModal = 'viewEmployee'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        View Detail
                                                    </button>

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
                                <tr x-show="searchEmployee.trim() === ''">
                                    <td colspan="8"
                                        class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Employees found.
                                    </td>
                                </tr>
                            @endforelse

                            @foreach ($allEmployees as $employee)
                                <tr class="border-t border-gray-200"
                                    x-show="searchEmployee.trim() !== '' && 
                                        {{ Js::from(strtolower($employee->fullname . ' ' . ($employee->address ?? ''))) }}
                                        .includes(searchEmployee.toLowerCase())">
                                    <td class="py-2 px-4">{{ $loop->iteration }}</td>
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
                                    <td class="py-2 px-4 max-w-64">
                                        <div class="truncate" title="{{ $employee->address ?? '-' }}">
                                            {{ $employee->address ?? '-' }}
                                        </div>
                                    </td>

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
                                            <button x-ref="button" @click="checkPosition(); open = !open" type="button"
                                                class="cursor-pointer inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                                                title="Actions">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" @click.away="open = false" x-transition
                                                :style="dropdownStyle"
                                                class="rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-[9999]">
                                                <div class="py-1">
                                                    <button
                                                        @click="setViewEmployee({{ $employee->toJson() }}); openModal = 'viewEmployee'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        View Detail
                                                    </button>

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
                            @endforeach

                            <tr x-show="searchEmployee.trim() !== '' && !hasResults">
                                <td colspan="8" class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                    No results found for "<span x-text="searchEmployee"></span>"
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            <div class="mt-4" id="employees-pagination-container" x-show="searchEmployee.trim() === ''">
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
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs px-4 py-6">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-2xl flex flex-col overflow-hidden" style="height: min(calc(100vh - 6rem), 700px); min-height: 0; display: flex; flex-direction: column;">
                {{-- Fixed Header --}}
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-white rounded-t-xl flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Employee</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>

                {{-- Scrollable Content --}}
                <div class="overflow-y-auto overflow-x-hidden flex-1 px-6 py-4">
                <form id="editEmployeeForm" :action="`{{ route('owner.manage-data.employees.index') }}/${editEmployee.id}`" method="POST"
                    @submit.prevent="
                        if (validateEditEmployee()) {
                            $el.submit();
                        }
                    "
                    class="space-y-4">
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
                </form>
                </div>

                {{-- Fixed Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 flex-shrink-0 bg-white rounded-b-xl">
                    <button type="button" @click="openModal=null"
                        class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer">Cancel</button>
                    <button type="submit" form="editEmployeeForm"
                        class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark cursor-pointer">Update</button>
                </div>
            </div>
        </div>

        {{-- ========== View Employee Detail Modal ========== --}}
        <div x-show="openModal === 'viewEmployee'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs px-4 py-6">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-2xl flex flex-col overflow-hidden" style="height: min(calc(100vh - 6rem), 600px); min-height: 0; display: flex; flex-direction: column;">
                {{-- Fixed Header --}}
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-white rounded-t-xl flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900">Employee Detail</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>

                {{-- Scrollable Content --}}
                <div class="overflow-y-auto overflow-x-hidden flex-1 px-6 py-4">
                    <div class="space-y-4 max-w-full">
                    {{-- Full Name --}}
                    <div class="border-b border-gray-100 pb-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Full Name</label>
                        <p class="text-sm font-medium text-gray-900 break-words" x-text="viewEmployee.fullname || '-'"></p>
                    </div>

                    {{-- Phone Number --}}
                    <div class="border-b border-gray-100 pb-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Phone Number</label>
                        <p class="text-sm text-gray-900 break-words" x-text="viewEmployee.phone_number || '-'"></p>
                    </div>

                    {{-- Role --}}
                    <div class="border-b border-gray-100 pb-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Role</label>
                        <p class="text-sm text-gray-900">
                            <span x-show="viewEmployee.role === 'owner'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                Owner
                            </span>
                            <span x-show="viewEmployee.role === 'admin'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Admin
                            </span>
                            <span x-show="viewEmployee.role === 'pm'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Project Manager
                            </span>
                            <span x-show="viewEmployee.role === 'karyawan'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Karyawan
                            </span>
                        </p>
                    </div>

                    {{-- Birth Date --}}
                    <div class="border-b border-gray-100 pb-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Birth Date</label>
                        <p class="text-sm text-gray-900">
                            <template x-if="viewEmployee.birth_date">
                                <span x-text="new Date(viewEmployee.birth_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })"></span>
                            </template>
                            <template x-if="!viewEmployee.birth_date">
                                <span>-</span>
                            </template>
                        </p>
                    </div>

                    {{-- Work Date --}}
                    <div class="border-b border-gray-100 pb-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Work Date</label>
                        <p class="text-sm text-gray-900 break-words" x-text="viewEmployee.work_date || '-'"></p>
                    </div>

                    {{-- Dress Size --}}
                    <div class="border-b border-gray-100 pb-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Dress Size</label>
                        <p class="text-sm text-gray-900" x-text="viewEmployee.dress_size || '-'"></p>
                    </div>

                    {{-- Salary System --}}
                    <div class="border-b border-gray-100 pb-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Salary System</label>
                        <p class="text-sm text-gray-900">
                            <template x-if="viewEmployee.salary_system && viewEmployee.salary_cycle">
                                <span x-text="`${viewEmployee.salary_system} (${viewEmployee.salary_cycle}x)`"></span>
                            </template>
                            <template x-if="!viewEmployee.salary_system || !viewEmployee.salary_cycle">
                                <span x-text="viewEmployee.salary_system || '-'"></span>
                            </template>
                        </p>
                    </div>

                    {{-- Address with Copy Icon --}}
                    <div class="border-b border-gray-100 pb-3">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Address</label>
                        <div class="flex items-start gap-2 max-w-full">
                            <p class="text-sm text-gray-900 flex-1 break-all min-w-0" x-text="viewEmployee.address || '-'"></p>
                            <template x-if="viewEmployee.address">
                                <button 
                                    type="button"
                                    :data-text="viewEmployee.address"
                                    onclick="copyEmployeeText(this)"
                                    class="relative text-gray-400 hover:text-primary transition-colors cursor-pointer flex-shrink-0 mt-0.5"
                                    title="Copy address">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                    </div>
                </div>

                {{-- Fixed Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-white rounded-b-xl flex-shrink-0">
                    <button type="button" @click="openModal=null"
                        class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- AJAX Pagination for Employees --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination();
        });

        function setupPagination() {
            const paginationContainer = document.getElementById('employees-pagination-container');
            if (!paginationContainer) return;

            paginationContainer.addEventListener('click', function(e) {
                const link = e.target.closest('a[href]');
                if (!link) return;

                e.preventDefault();
                const url = link.getAttribute('href');

                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const newSection = doc.querySelector('#employees-section');
                    const currentSection = document.querySelector('#employees-section');
                    
                    if (newSection && currentSection) {
                        currentSection.innerHTML = newSection.innerHTML;
                        setupPagination();
                        
                        // Scroll to section top
                        currentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        }
    </script>

    {{-- Copy to Clipboard Function for Employee --}}
    <script>
        function copyEmployeeText(button) {
            const text = button.getAttribute('data-text');
            
            if (!text) {
                console.error('No text to copy');
                return;
            }
            
            // Fallback copy method (works in all browsers)
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    console.log('Copied:', text);
                    
                    // Create tooltip
                    const tooltip = document.createElement('div');
                    tooltip.textContent = 'Copied!';
                    tooltip.className = 'absolute bg-gray-900 text-white text-xs px-2 py-1 rounded shadow-lg -top-8 left-1/2 transform -translate-x-1/2 z-50';
                    tooltip.style.whiteSpace = 'nowrap';
                    
                    // Position relative to button
                    button.style.position = 'relative';
                    button.appendChild(tooltip);
                    
                    // Remove tooltip after 1.5 seconds
                    setTimeout(function() {
                        tooltip.remove();
                    }, 1500);
                }
            } catch (err) {
                console.error('Failed to copy:', err);
            }
            
            document.body.removeChild(textarea);
        }
    </script>
@endpush
