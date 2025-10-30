@php
    $role = auth()->user()->role ?? null;

    // Biar logo kliknya ke dashboard sesuai role
    $dashboardRouteName = match ($role) {
        'owner' => 'owner.dashboard',
        'admin' => 'admin.dashboard',
        'pm' => 'pm.dashboard',
        'karyawan' => 'karyawan.dashboard',
        default => 'login',
    };
@endphp
<div class="flex flex-col h-screen bg-white border-r border-gray-light w-64">
    <!-- Logo -->
    <div class="flex items-center justify-center h-16 border-b border-gray-light">
        <a href="{{ route($dashboardRouteName) }}" class="text-2xl font-bold text-primary">STGR</a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-6 text-sm text-font-base">
        {{-- ================= OWNER ONLY ================= --}}
        @if ($role === 'owner')
            <div class="mb-4">
                <p class="px-4 text-xs font-semibold text-gray-dark uppercase mb-2">Menu</p>

                <ul class="space-y-2">
                    <!-- Dashboard -->
                    <li>
                        <x-sidebar-menu.main-menu href="{{ route('owner.dashboard') }}" :pattern="'owner.dashboard'">
                            <x-icons.dashboard class="text-current" />
                            <span class="ml-2">Dashboard</span>
                        </x-sidebar-menu.main-menu>
                    </li>

                    <!-- Manage Data -->
                    <li x-data="{
                        open: @js(request()->routeIs('owner.manage-data.products.*') || request()->is('owner/manage-data/products/*') || request()->routeIs('owner.manage-data.work-order.*') || request()->is('owner/manage-data/work-order/*') || request()->routeIs('owner.manage-data.users-sales.*') || request()->is('owner/manage-data/users-sales/*'))
                    }">
                        <button type="button" @click="open = !open"
                            class="flex items-center justify-between w-full pl-6 pr-4 py-3 hover:bg-gray-light focus:outline-none cursor-pointer">
                            <span class="flex items-center">
                                @php
                                    $mdActive =
                                        request()->routeIs('owner.manage-data.products.*') ||
                                        request()->is('owner/manage-data/products.*') ||
                                        request()->routeIs('owner.manage-data.work-order.*') ||
                                        request()->is('owner/manage-data/work-order.*') ||
                                        request()->routeIs('owner.manage-data.users-sales.*') ||
                                        request()->is('owner/manage-data/users-sales.*');
                                @endphp
                                <x-icons.manage-data />
                                <span class="ml-2">Manage Data</span>
                            </span>
                            <x-icons.right-arrow class="text-font-base transition-transform duration-200"
                                x-bind:class="open ? 'rotate-90' : ''" />
                        </button>

                        <ul class="mt-1 space-y-2 font-normal" x-show="open" x-transition x-cloak>
                            <li>
                                <x-sidebar-menu.sub-menu href="{{ route('owner.manage-data.products.index') }}"
                                    :pattern="['owner.manage-data.products.*', 'owner/manage-data/products/*']">
                                    Products
                                </x-sidebar-menu.sub-menu>
                            </li>

                            {{-- Nanti sesuaikan setelah route-nya ada --}}
                            <li>
                                <x-sidebar-menu.sub-menu href="{{ route('owner.manage-data.work-order.index') }}"
                                    :pattern="['owner.manage-data.work-order.*', 'owner/manage-data/work-order/*']">
                                    WO Data
                                </x-sidebar-menu.sub-menu>
                            </li>
                            <li>
                                <x-sidebar-menu.sub-menu href="{{ route('owner.manage-data.users-sales.index') }}"
                                    :pattern="['owner.manage-data.users-sales.*', 'owner/manage-data/users-sales/*']">
                                    Users & Sales
                                </x-sidebar-menu.sub-menu>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        @endif

        {{-- ================= ADMIN MENU ================= --}}
        @if (in_array($role, ['owner', 'admin']))
            <div class="mb-4">
                @if ($role === 'owner')
                    <p class="px-4 text-xs font-semibold text-gray-dark uppercase mb-2">ADMIN</p>
                @elseif ($role === 'admin')
                    <p class="px-4 text-xs font-semibold text-gray-dark uppercase mb-2">MENU</p>
                @endif

                <ul class="space-y-2">
                    @if ($role === 'admin')
                        <!-- Dashboard -->
                        <li>
                            <x-sidebar-menu.main-menu href="{{ route('admin.dashboard') }}" :pattern="'admin.dashboard'">
                                <x-icons.dashboard class="text-current" />
                                <span class="ml-2">Dashboard</span>
                            </x-sidebar-menu.main-menu>
                        </li>
                    @endif
                    <!-- Orders -->
                    <li>
                        <x-sidebar-menu.main-menu href="{{ route('admin.orders.index') }}" :pattern="'admin.orders.*'">
                            <x-icons.orders class="text-current" />
                            <span class="ml-2">Orders</span>
                        </x-sidebar-menu.main-menu>
                    </li>
                    <!-- Delivery Orders -->
                    <li>
                        <x-sidebar-menu.main-menu href="{{ route('admin.delivery-orders') }}" :pattern="'admin.delivery-orders'">
                            <x-icons.delivery-orders class="text-current" />
                            <span class="ml-2">Delivery Orders</span>
                        </x-sidebar-menu.main-menu>
                    </li>
                    <li>
                        <x-sidebar-menu.main-menu href="{{ route('admin.work-orders') }}" :pattern="'admin.work-orders'">
                            <x-icons.work-orders class="text-current" />
                            <span class="ml-2">Work Orders</span>
                        </x-sidebar-menu.main-menu>
                    </li>
                    <li>
                        <x-sidebar-menu.main-menu
                            href="{{ route($role === 'owner' ? 'owner.payment-history' : 'admin.payment-history') }}"
                            :pattern="$role === 'owner' ? 'owner.payment-history' : 'admin.payment-history'">
                            <x-icons.payment-history class="text-current" />
                            <span class="ml-2">Payment History</span>
                        </x-sidebar-menu.main-menu>
                    </li>
                    <li>
                        <x-sidebar-menu.main-menu href="{{ route('admin.customers.index') }}" :pattern="'admin.customers*'">
                            <x-icons.customers class="text-current" />
                            <span class="ml-2">Customers</span>
                        </x-sidebar-menu.main-menu>
                    </li>

                </ul>
            </div>
        @endif

        {{-- ================= PM MENU ================= --}}
        @if (in_array($role, ['owner', 'pm']))
            <div class="mb-4">
                @if ($role === 'owner')
                    <p class="px-4 text-xs font-semibold text-gray-dark uppercase mb-2">PRODUCT MANAGER</p>
                @elseif ($role === 'pm')
                    <p class="px-4 text-xs font-semibold text-gray-dark uppercase mb-2">MENU</p>
                @endif

                <ul class="space-y-2">
                    @if ($role === 'pm')
                        <!-- Dashboard -->
                        <li>
                            <x-sidebar-menu.main-menu href="{{ route('pm.dashboard') }}" :pattern="'pm.dashboard'">
                                <x-icons.dashboard class="text-current" />
                                <span class="ml-2">Dashboard</span>
                            </x-sidebar-menu.main-menu>
                        </li>
                    @endif
                    <li>
                        <x-sidebar-menu.main-menu href="{{ route('pm.manage-task') }}" :pattern="'pm.manage-task'">
                            <x-icons.manage-task class="text-current" />
                            <span class="ml-2">Manage Task</span>
                        </x-sidebar-menu.main-menu>
                    </li>
                </ul>
            </div>
        @endif

        {{-- ================= KARYAWAN MENU (for Admin, PM, and Karyawan) ================= --}}
        @if (in_array($role, ['owner', 'admin', 'pm', 'karyawan']))
            <div class="mb-4">
                @if (in_array($role, ['owner', 'admin', 'pm']))
                    <p class="px-4 text-xs font-semibold text-gray-dark uppercase mb-2">KARYAWAN</p>
                @elseif ($role === 'karyawan')
                    <p class="px-4 text-xs font-semibold text-gray-dark uppercase mb-2">MENU</p>
                @endif

                <ul class="space-y-2">
                    @if ($role === 'karyawan')
                        <!-- Dashboard -->
                        <li>
                            <x-sidebar-menu.main-menu href="{{ route('karyawan.dashboard') }}" :pattern="'karyawan.dashboard'">
                                <x-icons.dashboard class="text-current" />
                                <span class="ml-2">Dashboard</span>
                            </x-sidebar-menu.main-menu>
                        </li>
                    @endif
                    <li>
                        <x-sidebar-menu.main-menu href="{{ route('karyawan.task') }}" :pattern="'karyawan.task'">
                            <x-icons.task class="text-current" />
                            <span class="ml-2">Task</span>
                        </x-sidebar-menu.main-menu>
                    </li>
                </ul>
            </div>
        @endif

        <!-- Logout -->
        <div class="px-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center justify-center px-6 py-3 rounded-md bg-alert-danger hover:bg-alert-danger-dark
                            text-white cursor-pointer">
                    <x-icons.logout class="text-white" />
                    <span class="font-medium">Logout</span>
                </button>
            </form>
        </div>
    </nav>
</div>
