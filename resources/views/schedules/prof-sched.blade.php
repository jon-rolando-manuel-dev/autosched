<x-app-layout>
    <x-slot name="tabname">
        <p class="text-white text-sm">
            PROFESSOR SCHEDULES
        </p>
    </x-slot>
    
    <!-- Content -->
    <div class="w-full h-full flex flex-col overflow-auto">
        <div class="title-btn flex w-full justify-between items-center border-b border-b-gray-300 overflow-x-auto overflow-y-hidden">
            <div class="sm:flex hidden">
                <div class="z-10 h-full w-fit rounded-br-3xl bg-b7 sm:flex hidden justify-center items-center shadow-md p-5">
                    <p class="whitespace-nowrap text-xl px-3 text-white tracking-widest">PROFESSOR SCHEDULES</p> 
                </div>                
            </div>

            <div class="m-3 flex items-center h-fit">
                <div class="pr-2">
                    {{-- <Link class="whitespace-nowrap squared-btn green-btn text-xs0 border group flex items-center justify-center" confirm="GENERATE SCHEDULE" confirm-text="Are You Sure?" confirm-button="Yes" cancel-button="Cancel" href="{{ route('generate.create') }}">
                        <div class="mr-2 flex items-center justify-center">
                            <i class="fa-solid fa-clock text-white group-hover:text-green-500"></i>
                        </div>
                        GENERATE CLASS SCHEDULES
                    </Link> --}}
                </div>
            </div>
        </div>

        <div class="overflow-auto h-full w-full flex flex-wrap justify-center p-3">
            <div class="w-full h-full flex flex-wrap justify-center xl:w-tabw">
                <x-splade-table  class="w-full mobtable" :for="$Prof_sched" striped>
                </x-splade-table>

                {{-- <div class="h-full w-full flex items-center justify-center">
                    <div class="h-full w-full flex items-center flex-wrap justify-center p-2 gap-3">
                        <i class="text-2xl text-gray-500 fa-solid fa-screwdriver-wrench"></i>
                        <p class="text-2xl text-gray-500">
                            UNDER CONSTRUCTION
                        </p>
                    </div>
                </div> --}}
            </div>
        </div>
    </div>
    <!-- End -->
</x-app-layout>


