

<x-app-layout>
    <x-slot name="tabname">
        <p class="text-white text-sm">
            COURSES
        </p>
    </x-slot>

    <!-- Content -->
    <div class="w-full h-full flex flex-col overflow-auto">
        <div class="title-btn flex w-full justify-between items-center overflow-x-auto overflow-y-hidden">
            <div class="sm:flex hidden h-full">
                <div class="z-10 h-full w-fit rounded-br-3xl bg-b7 flex justify-center items-center shadow-md p-5">
                    <p class="whitespace-nowrap text-xl px-3 text-white tracking-widest">COURSES</p> 
                </div>
            </div>

            {{-- <div class="m-3 flex items-center h-fit">
                <Link class="whitespace-nowrap squared-btn blue-btn text-xs0 border group flex items-center justify-center" onclick="start()" href="{{ route('rooms.create') }}">
                    <div class="mr-2 flex items-center justify-center">
                        <i class=" mr-xs fa-solid fa-plus text-xs1 sm:group-hover:text-b6 text-white"></i>
                        <i class="fa-solid fa-door-open sm:group-hover:text-b6 text-white"></i>
                    </div>

                    ADD ROOM
                </Link>
            </div> --}}

            {{-- <div class="m-3 sm:flex items-center h-fit hidden">
                <Link modal class="whitespace-nowrap squared-btn blue-btn text-xs0 border group flex items-center justify-center" onclick="start()" href="{{ route('rooms.create') }}">
                    <div class="mr-2 flex items-center justify-center">
                        <i class=" mr-xs fa-solid fa-plus text-xs1 sm:group-hover:text-b6 text-white"></i>
                        <i class="fa-solid fa-door-open sm:group-hover:text-b6 text-white"></i>
                    </div>

                    ADD ROOM
                </Link>
            </div> --}}
        </div>

        <div class="overflow-auto h-full w-full flex justify-center p-3 items-center">
            <div class="h-full w-full flex justify-center 2xl:w-tabw 2xl:h-fit">
                <div class="w-full h-full">
                    <x-splade-table class="w-full h-full mobtable" :for="$courses" striped>
                        @cell('courseName', $course)
                            <div class="td" data-title="Course">
                                {{ $course->courseName }}
                            </div>
                        @endcell

                        @cell('courseDept', $course)
                            <div class="td" data-title="Department">
                                {{ $course->courseDept }}
                            </div>
                        @endcell

                        @cell('courseYearLevels', $course)
                            <div class="td" data-title="Year Levels">
                                {{ $course->courseYearLevels }}
                            </div>
                        @endcell

                        @cell('courseSchool', $course)
                            <div class="td" data-title="School Name">
                                {{ $course->courseSchool }}
                            </div>
                        @endcell

                        @cell('action', $course)
                            <div class="td" data-title="Actions">
                                <Link href="{{ route('generate', ['course' => $course->courseName]) }}" confirm="GENERATE SCHEDULE" confirm-text="Are You Sure?" confirm-button="Confirm" cancel-button="Cancel" class="flex group squared-btn orange-btn border w-12">
                                    <div class="h-full w-full relative">
                                        <div class="flex items-center justify-center h-full w-full">
                                            <i class="mr-xs fa-solid fa-plus text-xs1 sm:group-hover:text-orange-500 text-white"></i>
                                            <i class="fa-solid fa-clock sm:group-hover:text-orange-500 text-white"></i>
                                        </div>
        
                                        <div class="absolute bottom-8 -right-4 shadow-md bg-white bg-opacity-90 rounded-md p-2 hidden w-fit group-hover:block">
                                            <p class="text-orange-500 text-xs0 whitespace-pre">
                                                GENERATE SCHEDULES
                                            </p>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        @endcell
                    </x-splade-table>
                </div>
            </div>
        </div>
    </div>
    <!-- End -->
</x-app-layout>