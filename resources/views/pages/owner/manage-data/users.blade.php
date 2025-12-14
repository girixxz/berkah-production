@extends('layouts.app')
@section('title', 'Manage Users')
@section('content')
    <x-nav-locate :items="['Menu', 'Manage Data', 'Users']" />

    {{-- Root Alpine State --}}
    <div x-data="{
        openModal: '{{ session('openModal') }}',
        editUser: {},
        searchUser: '',
        showDeleteUserConfirm: null,
    
        // Add User Validation
        addUserForm: {
            fullname: '',
            username: '',
            phone_number: '',
            role: '',
            password: '',
            password_confirmation: ''
        },
        addUserErrors: {},
    
        validateAddUser() {
            this.addUserErrors = {};
    
            if (!this.addUserForm.fullname) {
                this.addUserErrors.fullname = 'Fullname is required';
            } else if (this.addUserForm.fullname.length > 255) {
                this.addUserErrors.fullname = 'Fullname must not exceed 255 characters';
            }
    
            if (!this.addUserForm.username) {
                this.addUserErrors.username = 'Username is required';
            } else if (this.addUserForm.username.length > 255) {
                this.addUserErrors.username = 'Username must not exceed 255 characters';
            }
    
            if (this.addUserForm.phone_number && this.addUserForm.phone_number.length > 100) {
                this.addUserErrors.phone_number = 'Phone must not exceed 100 characters';
            }
    
            if (!this.addUserForm.role) {
                this.addUserErrors.role = 'Role is required';
            } else if (!['owner', 'admin', 'pm', 'karyawan'].includes(this.addUserForm.role)) {
                this.addUserErrors.role = 'Please select a valid role';
            }
    
            if (!this.addUserForm.password) {
                this.addUserErrors.password = 'Password is required';
            } else if (this.addUserForm.password.length < 6) {
                this.addUserErrors.password = 'Password must be at least 6 characters';
            }
    
            if (!this.addUserForm.password_confirmation) {
                this.addUserErrors.password_confirmation = 'Confirm Password is required';
            } else if (this.addUserForm.password !== this.addUserForm.password_confirmation) {
                this.addUserErrors.password_confirmation = 'Passwords do not match';
            }
    
            return Object.keys(this.addUserErrors).length === 0;
        },
    
        init() {
            // Auto scroll to section after Add/Edit/Delete operations
            const scrollTarget = '{{ session('scrollToSection') }}';
            if (scrollTarget) {
                setTimeout(() => {
                    const section = document.getElementById(scrollTarget);
                    if (section) {
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 300);
            }

            this.$watch('openModal', value => {
                if (value) {
                    setTimeout(() => {
                        const modalEl = document.querySelector('[x-show=\'openModal === \\\'' + value + '\\\'\']');
                        if (modalEl) {
                            modalEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 100);
                }
    
                // Reset form saat modal dibuka
                if (value === 'addUser') {
                    this.addUserForm = {
                        fullname: '',
                        username: '',
                        phone_number: '',
                        role: '',
                        password: '',
                        password_confirmation: ''
                    };
                    this.addUserErrors = {};
                }
            });
        }
    }">

        {{-- ===================== USERS SECTION ===================== --}}
        <section id="users-section" class="bg-white border border-gray-200 rounded-lg p-5">
            {{-- Header --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center mb-5">
                <h2 class="text-xl font-semibold text-gray-900">Manage Users</h2>

                <div class="md:ml-auto flex items-center gap-2 w-full md:w-auto">
                    {{-- Search --}}
                    <div class="flex-1">
                        <div class="relative">
                            <x-icons.search />
                            <input type="text" x-model="searchUser" placeholder="Search User"
                                class="w-full rounded-md border border-gray-300 pl-9 pr-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                        </div>
                    </div>

                    {{-- Add Users --}}
                    <button @click="openModal = 'addUser'"
                        class="cursor-pointer w-32 whitespace-nowrap px-3 py-2 rounded-md bg-primary text-white hover:bg-primary-dark text-sm text-center">
                        + Add User
                    </button>
                </div>
            </div>

            {{-- Table Users --}}
            <div class="overflow-x-auto">
                <div class="max-h-178 overflow-y-auto">
                    <table class="min-w-[750px] w-full text-sm">
                        <thead class="sticky top-0 bg-primary-light text-font-base z-10">
                            <tr>
                                <th class="py-2 px-4 text-left rounded-l-md">No</th>
                                <th class="py-2 px-4 text-left">User</th>
                                <th class="py-2 px-4 text-left">Username</th>
                                <th class="py-2 px-4 text-left">Phone</th>
                                <th class="py-2 px-4 text-left">Role</th>
                                <th class="py-2 px-4 text-right rounded-r-md">Action</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody" x-data="{
                            get hasResults() {
                                const search = searchUser.trim().toLowerCase();
                                if (search === '') return true;
                                @foreach ($allUsers as $u)
                                    if ('{{ strtolower($u->fullname . ' ' . $u->username . ' ' . ($u->phone_number ?? '') . ' ' . $u->role) }}'.includes(search)) return true;
                                @endforeach
                                return false;
                            }
                        }">
                            @forelse ($users as $user)
                                <tr class="border-t border-gray-200" x-show="searchUser.trim() === ''">
                                    <td class="py-2 px-4">
                                        {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                                    <td class="py-2 px-4">
                                        <div class="flex items-center gap-3">
                                            @php
                                                $avatarUrl = !empty($user->img_url)
                                                    ? $user->img_url
                                                    : 'https://i.pravatar.cc/40?u=' .
                                                        urlencode($user->id ?? $user->username);
                                            @endphp
                                            <img src="{{ $avatarUrl }}" alt="{{ $user->fullname }}"
                                                class="w-8 h-8 rounded-full object-cover border" />
                                            <span>{{ $user->fullname }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4">{{ $user->username }}</td>
                                    <td class="py-2 px-4">{{ $user->phone_number ?? '-' }}</td>
                                    <td class="py-2 px-4">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium
                                            {{ $user->role === 'owner' ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $user->role === 'admin' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $user->role === 'pm' ? 'bg-primary-light text-primary-dark' : '' }}
                                            {{ $user->role === 'karyawan' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
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
                                                    {{-- Edit --}}
                                                    <button
                                                        @click="editUser = {{ $user->toJson() }}; openModal = 'editUser'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>

                                                    {{-- Delete --}}
                                                    <button
                                                        @click="showDeleteUserConfirm = {{ $user->id }}; open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr x-show="searchUser.trim() === ''">
                                    <td colspan="6" class="py-3 px-4 text-center text-red-500 border-t border-gray-200">
                                        No Users found.
                                    </td>
                                </tr>
                            @endforelse

                            {{-- ALL Users for Search --}}
                            @foreach ($allUsers as $user)
                                <tr x-show="searchUser.trim() !== '' && '{{ strtolower($user->fullname . ' ' . $user->username . ' ' . ($user->phone_number ?? '') . ' ' . $user->role) }}'.includes(searchUser.trim().toLowerCase())">
                                    <td class="py-2 px-4">{{ $loop->iteration }}</td>
                                    <td class="py-2 px-4">
                                        <div class="flex items-center gap-3">
                                            @php
                                                $avatarUrl = !empty($user->img_url)
                                                    ? $user->img_url
                                                    : 'https://i.pravatar.cc/40?u=' .
                                                        urlencode($user->id ?? $user->username);
                                            @endphp
                                            <img src="{{ $avatarUrl }}" alt="{{ $user->fullname }}"
                                                class="w-8 h-8 rounded-full object-cover border" />
                                            <span>{{ $user->fullname }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4">{{ $user->username }}</td>
                                    <td class="py-2 px-4">{{ $user->phone_number ?? '-' }}</td>
                                    <td class="py-2 px-4">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium
                                            {{ $user->role === 'owner' ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $user->role === 'admin' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $user->role === 'pm' ? 'bg-primary-light text-primary-dark' : '' }}
                                            {{ $user->role === 'karyawan' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ ucfirst($user->role) }}
                                        </span>
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
                                                        @click="editUser = {{ $user->toJson() }}; openModal = 'editUser'; open = false"
                                                        class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>

                                                    <button
                                                        @click="showDeleteUserConfirm = {{ $user->id }}; open = false"
                                                        type="button"
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            {{-- No Results Found for Search --}}
                            <tr x-show="searchUser.trim() !== '' && !hasResults">
                                <td colspan="6" class="py-16 text-center border-t border-gray-200">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <p class="text-gray-500 text-lg">No results found for "<span
                                                x-text="searchUser"></span>"</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination Users --}}
            <div class="mt-4" id="users-pagination-container" x-show="searchUser.trim() === ''">
                <x-custom-pagination :paginator="$users" />
            </div>
        </section>

        {{-- ===================== MODALS ===================== --}}
        {{-- ========== Add User Modal ========== --}}
        <div x-show="openModal === 'addUser'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs px-4 py-6">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg" style="height: min(calc(100vh - 3rem), 600px); min-height: 0; display: flex; flex-direction: column;">
                {{-- Fixed Header --}}
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 flex-shrink-0 bg-white rounded-t-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Add New User</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                
                {{-- Scrollable Content --}}
                <div class="overflow-y-auto overflow-x-hidden flex-1 px-6 py-4">
                    <form id="addUserForm" action="{{ route('owner.manage-data.users.store') }}" method="POST"
                        @submit="if (!validateAddUser()) $event.preventDefault()" class="space-y-4">
                        @csrf
                    {{-- Full Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fullname <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="fullname" x-model="addUserForm.fullname"
                                @blur="validateAddUser()"
                                :class="addUserErrors.fullname || {{ $errors->has('fullname') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->has('fullname'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addUserErrors.fullname" x-text="addUserErrors.fullname"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('fullname')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- Username --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="username" x-model="addUserForm.username"
                                @blur="validateAddUser()"
                                :class="addUserErrors.username || {{ $errors->has('username') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->has('username'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addUserErrors.username" x-text="addUserErrors.username"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- Phone --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone (optional)</label>
                        <input type="tel" name="phone_number" x-model="addUserForm.phone_number"
                            @blur="validateAddUser()" placeholder="e.g., 081234567890"
                            :class="addUserErrors.phone_number ? 'border-red-500' : 'border-gray-200'"
                            class="mt-1 w-full rounded-md border px-4 py-2 text-sm text-gray-500 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" />
                        <p x-show="addUserErrors.phone_number" x-text="addUserErrors.phone_number"
                            class="mt-1 text-sm text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Roles <span
                                class="text-red-500">*</span></label>
                        <div x-data="{
                            open: false,
                            options: [
                                { value: 'owner', name: 'Owner' },
                                { value: 'admin', name: 'Admin' },
                                { value: 'pm', name: 'Project Manager' },
                                { value: 'karyawan', name: 'Karyawan' }
                            ],
                            selected: null,
                            selectedValue: '{{ old('role') }}',
                            
                            init() {
                                if (this.selectedValue) {
                                    this.selected = this.options.find(o => o.value === this.selectedValue) || null;
                                    addUserForm.role = this.selectedValue;
                                }
                            },
                            
                            select(option) {
                                this.selected = option;
                                this.selectedValue = option.value;
                                this.open = false;
                                addUserForm.role = option.value;
                                if (addUserErrors.role) {
                                    delete addUserErrors.role;
                                }
                            }
                        }" class="relative w-full">
                            {{-- Trigger --}}
                            <button type="button" @click="open = !open" @blur="validateAddUser()"
                                :class="addUserErrors.role ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full flex justify-between items-center rounded-md border px-4 py-2 text-sm bg-white
                                       focus:outline-none focus:ring-2 transition-colors">
                                <span x-text="selected ? selected.name : 'Select Role'"
                                    :class="!selected ? 'text-gray-400' : 'text-gray-500'"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Hidden input --}}
                            <input type="hidden" name="role" x-model="selectedValue">

                            {{-- Dropdown --}}
                            <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                <ul class="max-h-60 overflow-y-auto py-1">
                                    <template x-for="option in options" :key="option.value">
                                        <li @click="select(option)"
                                            class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                            :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.value === option.value }">
                                            <span x-text="option.name"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                        <p x-show="addUserErrors.role" x-text="addUserErrors.role" class="mt-1 text-sm text-red-600"></p>
                    </div>
                    {{-- Password --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="password" x-model="addUserForm.password"
                                @blur="validateAddUser()"
                                :class="addUserErrors.password || {{ $errors->has('password') ? 'true' : 'false' }} ?
                                    'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                    'border-gray-200 focus:border-primary focus:ring-primary/20'"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->has('password'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">
                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        <p x-show="addUserErrors.password" x-text="addUserErrors.password"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- Confirm Password --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password <span
                                class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" x-model="addUserForm.password_confirmation"
                            @blur="validateAddUser()"
                            :class="addUserErrors.password_confirmation ||
                                {{ $errors->has('password_confirmation') ? 'true' : 'false' }} ?
                                'border-red-500 focus:border-red-500 focus:ring-red-200' :
                                'border-gray-200 focus:border-primary focus:ring-primary/20'"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border focus:outline-none focus:ring-2 text-gray-700">
                        <p x-show="addUserErrors.password_confirmation" x-text="addUserErrors.password_confirmation"
                            class="mt-1 text-sm text-red-600"></p>
                        @error('password_confirmation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    </form>
                </div>
                
                {{-- Fixed Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 flex-shrink-0 bg-white rounded-b-xl">
                    <button type="button" @click="openModal=null"
                        class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer">Cancel</button>
                    <button type="submit" form="addUserForm"
                        class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark cursor-pointer">Save</button>
                </div>
            </div>
        </div>

        {{-- ========== Edit User Modal ========== --}}
        <div x-show="openModal === 'editUser'" x-cloak x-init="@if (session('openModal') === 'editUser' && session('editUserId')) editUser = {{ \App\Models\User::find(session('editUserId'))->toJson() }}; @endif"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity px-4">
            <div @click.away="openModal=null" class="bg-white rounded-xl shadow-lg w-full max-w-lg">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit User</h3>
                    <button @click="openModal=null" class="text-gray-400 hover:text-gray-600 cursor-pointer">✕</button>
                </div>
                <form :action="`{{ route('owner.manage-data.users.index') }}/${editUser.id}`" method="POST"
                    class="px-6 py-4 space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Full Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="fullname" x-model="editUser.fullname" required maxlength="255"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->has('fullname') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('fullname')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- Username --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="username" x-model="editUser.username" required maxlength="255"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->has('username') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->has('username'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">

                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        @error('username')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- Phone --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" name="phone_number" x-model="editUser.phone_number" maxlength="100"
                            pattern="[0-9+\-\s()]+" placeholder="e.g., 081234567890"
                            class="mt-1 w-full rounded-md border border-gray-200 px-4 py-2 text-sm 
                           text-gray-700 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    {{-- Role --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role <span
                                class="text-red-500">*</span></label>
                        <div x-data="{
                            open: false,
                            options: [
                                { value: 'owner', name: 'Owner' },
                                { value: 'admin', name: 'Admin' },
                                { value: 'pm', name: 'Project Manager' },
                                { value: 'karyawan', name: 'Karyawan' }
                            ],
                            selected: null,
                            selectedValue: '',
                            
                            loadRole() {
                                if (editUser && editUser.role) {
                                    this.selectedValue = editUser.role;
                                    this.selected = this.options.find(o => o.value === editUser.role) || null;
                                }
                            },
                            
                            init() {
                                // Load saat init
                                this.$nextTick(() => {
                                    this.loadRole();
                                });
                                
                                // Watch editUser.role untuk update otomatis
                                this.$watch('editUser.role', () => {
                                    this.loadRole();
                                });
                                
                                // Sync perubahan ke editUser.role
                                this.$watch('selectedValue', value => {
                                    if (value && editUser) {
                                        editUser.role = value;
                                    }
                                });
                            },
                            
                            select(option) {
                                this.selected = option;
                                this.selectedValue = option.value;
                                this.open = false;
                                if (editUser) {
                                    editUser.role = option.value;
                                }
                            }
                        }" class="relative w-full">
                            {{-- Trigger --}}
                            <button type="button" @click="open = !open"
                                class="mt-1 w-full flex justify-between items-center rounded-md border border-gray-200 px-4 py-2 text-sm bg-white
                                       focus:outline-none focus:ring-2 focus:border-primary focus:ring-primary/20 transition-colors">
                                <span x-text="selected ? selected.name : 'Select Role'"
                                    :class="!selected ? 'text-gray-400' : 'text-gray-700'"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Hidden input --}}
                            <input type="hidden" name="role" x-model="selectedValue">

                            {{-- Dropdown --}}
                            <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg">
                                <ul class="max-h-60 overflow-y-auto py-1">
                                    <template x-for="option in options" :key="option.value">
                                        <li @click="select(option)"
                                            class="px-4 py-2 cursor-pointer text-sm text-gray-700 hover:bg-primary/5 transition-colors"
                                            :class="{ 'bg-primary/10 font-medium text-primary': selected && selected.value === option.value }">
                                            <span x-text="option.name"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>
                    {{-- Password --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password (leave blank to keep
                            current)</label>
                        <div class="relative">
                            <input type="password" name="password" minlength="6"
                                class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->has('password') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">
                            @if ($errors->has('password'))
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 pointer-events-none">

                                    <x-icons.danger />
                                </span>
                            @endif
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- Confirm Password --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" name="password_confirmation" minlength="6"
                            class="mt-1 w-full rounded-md px-4 py-2 text-sm border {{ $errors->has('password_confirmation') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-gray-200 focus:border-primary focus:ring-primary/20' }} focus:outline-none focus:ring-2 text-gray-700">

                        @error('password_confirmation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="openModal=null"
                            class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 cursor-pointer">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-md bg-primary text-white hover:bg-primary-dark cursor-pointer">Update</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ================= DELETE USER CONFIRMATION MODAL ================= --}}
        <div x-show="showDeleteUserConfirm !== null" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black/50 bg-opacity-50 backdrop-blur-xs transition-opacity">
            <div @click.away="showDeleteUserConfirm = null"
                class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                {{-- Icon --}}
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                {{-- Title --}}
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                    Delete User?
                </h3>

                {{-- Message --}}
                <p class="text-sm text-gray-600 text-center mb-6">
                    Are you sure you want to delete this user? This action cannot be undone and all user data will be
                    permanently removed.
                </p>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="button" @click="showDeleteUserConfirm = null"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <form :action="'{{ route('owner.manage-data.users.index') }}/' + showDeleteUserConfirm"
                        method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors">
                            Yes, Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- AJAX Pagination Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setupPagination();
        });

        document.addEventListener('turbo:load', function() {
            setupPagination();
        });

        function setupPagination() {
            const container = document.getElementById('users-pagination-container');
            if (!container) return;

            container.addEventListener('click', function(e) {
                const link = e.target.closest('a[href*="page="]');
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

                        // Update the section content
                        const newSection = doc.getElementById('users-section');
                        const currentSection = document.getElementById('users-section');
                        if (newSection && currentSection) {
                            currentSection.innerHTML = newSection.innerHTML;
                        }

                        // Scroll to top of section
                        if (currentSection) {
                            setTimeout(() => {
                                currentSection.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });
                            }, 100);
                        }

                        // Re-setup pagination after update
                        setupPagination();
                    })
                    .catch(error => {
                        console.error('Error loading pagination:', error);
                    });
            });
        }
    </script>

@endsection
