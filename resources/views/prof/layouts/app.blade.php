<div class="flex text-blc1 bg-gray-100 relative h-full w-full overflow-hidden">
    <!-- Header -->
    <nav class="bg-b6 p-3 flex justify-between items-center shadow-md absolute top-0 z-10 w-full h-16">
        <div class="sm:hidden flex gap-3 items-center">
            <div class="cursor-pointer bg-white p-2 w-8 h-8 flex items-center justify-center rounded-md" id="menubtn" onclick="menuBtn()">
                <i class="fa-solid fa-bars text-b6 text-sm"></i>
            </div>

            <div>
                {{ $tabname }}
            </div>
        </div>

        <div class="sm:block hidden">
            <div class="flex items-center ml-3">
                <img src="{{ asset('image/logo-main.png') }}" alt="auto-sched-logo" class="w-11 mr-2">
                <p class="text-sm text-white">AUTO - SCHED | Welcome <!-- Prof Name --> {{ Auth::user()->profFName }} <!-- End --></p>
            </div>
        </div>

        <div class="flex gap-5 sm:mr-5 mr-2">
            <div class="flex items-center justify-center relative cursor-pointer">
                <span>
                    <i class="fa-solid fa-magnifying-glass text-white sm:text-xl text-sm"></i>
                </span>
            </div>

            <div class="flex items-center justify-center relative cursor-pointer">
                <span>
                    <i class="fa-solid fa-bell text-white sm:text-xl text-sm"></i>
                </span>
            </div>

            <div class="drop-btn flex items-center justify-center relative cursor-pointer">
                <x-dropdown placement="bottom-end">
                    <x-slot name="trigger">
                        <button>
                            <div>
                                <span>
                                    <i class="fa-solid fa-gear text-white sm:text-xl text-sm"></i>
                                </span>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link onclick="start()" href="{{ route('prof-update') }}">
                            <div class="flex items-center gap-2 uppercase whitespace-nowrap">
                                <i class="fa-solid fa-user-pen text-xs"></i> Update Your Profile
                            </div>
                        </x-dropdown-link>

                        <x-dropdown-link onclick="start()" href="{{ route('update-password') }}">
                            <div class="flex items-center gap-2 uppercase whitespace-nowrap">
                                <i class="fa-solid fa-lock text-xs"></i> Change Your Password
                            </div>
                        </x-dropdown-link>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </nav>
    <!-- End -->

    <!-- Mobile View Sidebar -->
    <div class="sidebar-cntnr flex sm:hidden fixed h-full w-full z-10 transition-all ease-out -translate-x-full">
        <div class="relative">
            <div id="closebtn" onclick="menuBtn()" class="flex menu-close-btn group border-2 border-b5 bg-b5 top-5 right-5">
                <i class="fa-solid fa-xmark text-sm text-white"></i>
            </div>
            
            @include('prof.layouts.sidebar')
        </div>
    </div>
    <!-- End -->

    <div class="flex h-full w-full pt-16">
        <!-- Sidebar -->
        <div class="sm:flex hidden">
            @include('prof.layouts.sidebar')
        </div>
        <!-- End -->

        <!-- Content -->
        {{ $slot }}
        <!-- End -->
    </div>
</div>