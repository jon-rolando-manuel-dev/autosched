<?php

namespace App\Http\Controllers;

use App\Models\All_Class;
use App\Models\Ongoing_Class_With_Subs;
use App\Models\Prof_config;
use App\Models\Prof_handle;
use App\Models\Prof_info;
use App\Models\Prof_sched;
use App\Models\Room;
use App\Models\Room_sched;
use App\Models\School_config;
use App\Models\School_info;
use App\Models\Stud_sched;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ProtoneMedia\Splade\Facades\Toast;

class GenerateController extends Controller
{
    //

    public function create($course)
    {

        // getting coordinator id
        $coorId = Auth::user()->id;

        $schName = School_info::where('coordinatorId',$coorId)->value('schName');

        $currentSem = School_config::where('coordinatorId',$coorId)->value('sem');

        $currentYear = date("Y");

        $schYear = null;

        if($currentSem > 1){
            $partnerYear = date('Y', strtotime($currentYear. ' - 1 years'));
            $schYear = $partnerYear."-".$currentYear;
        }else{
            $partnerYear = date('Y', strtotime($currentYear. ' + 1 years'));
            $schYear = $currentYear."-".$partnerYear;
        }

        $all_class = All_Class::select("*")->where('schName',$schName)->where('course',$course)->whereNull('hasSched')->orderBy("id")->get();

        // loop1
        foreach($all_class as $class){

            $all_class_id = $class->id;
            $allcourse = $class->course;
            $allyear = $class->year;
            $allsection = $class->section;
            $allschool = $class->schName;

            $subjects = Ongoing_Class_With_Subs::where('schName',$allschool)
                            ->where('course',$allcourse)
                            ->where('year',$allyear)
                            ->where('section',$allsection)
                            ->orderBy("id")->get();

            // loop2
            foreach($subjects as $subject){

                $subcourse = $subject->course;
                $subyear = $subject->year;
                $subsection = $subject->section;
                $subCode = $subject->subject_code;
                $subTitle = $subject->subject_title;
                $subSchool = $subject->schName;
                $subUnits = $subject->subject_units;
                $schId = $subject->schId;

                // checking if subject has schedule already
                $subjectHasSched = Prof_sched::select("*")
                                    ->where("profSchool", $subSchool)
                                    ->where("subCode", $subCode)
                                    ->where("studCourse", $subcourse)
                                    ->where("studYear", $subyear)
                                    ->where("studSection", $subsection)
                                    ->exists();

                if($subjectHasSched){
                    continue;
                }
                // end

                $field = Subject::where('subCode',$subCode)->where('subSchool',$subSchool)->value('subField');

                $professors = Prof_info::where('profSchool',$subSchool)->where('profField','LIKE',"%{$field}%")->orderBy("id")->get();

                // loop3
                foreach($professors as $professor){

                    $profId = $professor->id;
                    $profDept = $professor->profDept;
                    $profSchool = $professor->profSchool;
                    $profFName = $professor->profFName;
                    $profLName = $professor->profLName;
                    $profName = $profFName." ".$profLName;
                    $unitsTaken = Prof_sched::where('profId',$profId)->sum('totalHours');
                    $unitsTaken = $unitsTaken + $subUnits;
                    $unitsLimit = Prof_config::where('profId',$profId)->value('loadUnit');
                    $a = 3;

                    // checking if this prof has two subjects already on this specific course, year, section
                    $profSubCount = Stud_sched::where('profId', $profId)
                                                ->where("schName", $profSchool)
                                                ->where("studCourse", $subcourse)
                                                ->where("studYear", $subyear)
                                                ->where("studSection", $subsection)
                                                ->where("totalHours", '>=', $a)
                                                ->count();
                    // end

                    if($profSubCount >= 2){
                        continue;
                    }

                    if($unitsTaken <= $unitsLimit){

                        $days = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

                        // loop4
                        foreach ($days as $day) {
                            
                            if($day == "monday"){
                                $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartMon')->exists();
                                $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartMon');
                                $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndMon');
                            }elseif($day == "tuesday"){
                                $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartTue')->exists();
                                $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartTue');
                                $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndTue');
                            }elseif($day == "wednesday"){
                                $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartWed')->exists();
                                $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartWed');
                                $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndWed');
                            }elseif($day == "thursday"){
                                $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartThu')->exists();
                                $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartThu');
                                $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndThu');
                            }elseif($day == "friday"){
                                $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartFri')->exists();
                                $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartFri');
                                $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndFri');
                            }elseif($day == "saturday"){
                                $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartSat')->exists();
                                $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartSat');
                                $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndSat');
                            }elseif($day == "sunday"){
                                $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartSun')->exists();
                                $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartSun');
                                $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndSun');
                            }

                            if(!$available){
                                continue;
                            }

                            // getting total hours of duty of this prof for this specific day
                            $unitsToday = Prof_sched::where('profId',$profId)->where('schedDay',$day)->sum('totalHours');
                            $unitsToday = $unitsToday + $subUnits;

                            // making sure that this prof dont exceed 6 hours of duty in one day
                            if($unitsToday > 6){
                                continue;
                            }

                            // getting total class hours for this specific day,course,year,section
                            $unitsThisDay = Prof_sched::select("*")
                                                ->where("profSchool", $subSchool)
                                                // ->where("subCode", $subCode)
                                                ->where("studCourse", $subcourse)
                                                ->where("studYear", $subyear)
                                                ->where("studSection", $subsection)
                                                ->where("schedDay", $day)
                                                ->sum('totalHours');

                            $totalunitsThisDay = $unitsThisDay + $subUnits;

                            // making sure that this class dont exceed 8 hours of class in one day
                            if($totalunitsThisDay > 8){
                                continue;
                            }

                            $prefTimeStartSingleNum = str_replace(array('0',':'), '',$prefTimeStart);
                            $prefTimeEndSingleNum = str_replace(array('0',':'), '',$prefTimeEnd);
                            $totalProfFreeTime = $prefTimeEndSingleNum - $prefTimeStartSingleNum;

                            if($subUnits > $totalProfFreeTime){
                                $iteratorForSched = $totalProfFreeTime - 3;
                            }else{
                                $iteratorForSched = $totalProfFreeTime - $subUnits;
                            }

                            // loop5
                            for($i = 0; $i <= $iteratorForSched; $i++){

                                $timeMover = strtotime("0".$i.":00:00");
                                $testTime = strtotime("0".$subUnits.":00:00");
                                $prefTimeStartGoods = strtotime($prefTimeStart);
                                $prefTimeEndGoods = strtotime($prefTimeEnd);

                                $initialTimeStart = $prefTimeStartGoods + $timeMover;
                                $initialTimeEnd = $initialTimeStart + $testTime;
                                $finalTimeStart = date("H:i:s",$initialTimeStart);
                                $finalTimeEnd = date("H:i:s",$initialTimeEnd);

                                if( ($finalTimeEnd > $prefTimeEnd) && ($subUnits <= 3) ){
                                    continue;
                                }

                                if($finalTimeStart >= $prefTimeEnd){
                                    continue;
                                }

                                if( ($finalTimeEnd > $prefTimeEnd) && ($subUnits == 5) ){

                                    // $initialHalfSubUnits = $initialTimeEnd - $prefTimeEndGoods;
                                    // $finalHalfSubUnits = date("H:i:s",$initialHalfSubUnits);
                                    // $superFinalHalfSubUnits = str_replace(array('0',':'), '',$finalHalfSubUnits);
                                    $superFinalHalfSubUnits = 2;
                                    $superFinalFirstHalfSubUnits = 3;
                                    $testTime = strtotime("03:00:00");
                                    $initialTimeEnd = $initialTimeStart + $testTime;
                                    $finalTimeEnd = date("H:i:s",$initialTimeEnd);

                                    if($finalTimeEnd > $prefTimeEnd){
                                        continue;
                                    }

                                    if($finalTimeStart >= $prefTimeEnd){
                                        continue;
                                    }

                                    // making sure theres no conflict between each subject of this specific day,course,year,section
                                    $hasConflictWithOtherSub = Prof_sched::select("*")
                                    ->where("profSchool", $subSchool)
                                    // ->where("subCode", $subCode)
                                    ->where("studCourse", $subcourse)
                                    ->where("studYear", $subyear)
                                    ->where("studSection", $subsection)
                                    ->where("schedDay", $day)
                                    ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                        $query->where(function ($query1) use ($finalTimeStart) {
                                                    $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                        ->whereTime('endTime', '>', $finalTimeStart);
                                                })
                                                ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                    $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                        ->whereTime('endTime', '>=', $finalTimeEnd);
                                                })
                                                ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                    $query3->whereTime('startTime', '>', $finalTimeStart)
                                                        ->whereTime('endTime', '<', $finalTimeEnd);
                                                });
                                    })
                                    ->exists();

                                    if($hasConflictWithOtherSub){
                                        continue;
                                    }
                                    // end

                                    // making sure theres no conflict between each subject of this specific day and professor
                                    $hasConflictWithOtherProfSched = Prof_sched::select("*")
                                    ->where("profId", $profId)
                                    ->where("profSchool", $subSchool)
                                    // ->where("subCode", $subCode)
                                    ->where("schedDay", $day)
                                    ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                        $query->where(function ($query1) use ($finalTimeStart) {
                                                    $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                        ->whereTime('endTime', '>', $finalTimeStart);
                                                })
                                                ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                    $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                        ->whereTime('endTime', '>=', $finalTimeEnd);
                                                })
                                                ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                    $query3->whereTime('startTime', '>', $finalTimeStart)
                                                        ->whereTime('endTime', '<', $finalTimeEnd);
                                                });
                                    })
                                    ->exists();

                                    if($hasConflictWithOtherProfSched){
                                        continue;
                                    }
                                    // end

                                    // ///////////////////////////////////////////////////////////////////////////
                                    // making sure that this professor has proper lunch time either 12-1 or 1-2

                                    $twelve = strtotime("12:00:00");
                                    $finalTwelve = date("H:i:s",$twelve);

                                    $one = strtotime("13:00:00");
                                    $finalOne = date("H:i:s",$one);

                                    if( ($finalTimeStart <= $finalTwelve) && ($finalTimeEnd > $finalOne) ){
                                        continue;
                                    }

                                    // if($finalTimeStart >= $prefTimeEnd){
                                    //     continue;
                                    // }

                                    $hasSchedEndsOnTwelve = Prof_sched::select("*")
                                    ->where("profId", $profId)
                                    ->where("profSchool", $subSchool)
                                    // ->where("subCode", $subCode)
                                    ->where("schedDay", $day)
                                    ->whereTime('endTime', '=', $finalTwelve)
                                    ->exists();

                                    if( ($hasSchedEndsOnTwelve) && ($finalTimeStart == $finalTwelve) ){
                                        continue;
                                    }

                                    $hasSchedEndsOnOne = Prof_sched::select("*")
                                    ->where("profId", $profId)
                                    ->where("profSchool", $subSchool)
                                    // ->where("subCode", $subCode)
                                    ->where("schedDay", $day)
                                    ->whereTime('endTime', '=', $finalOne)
                                    ->exists();

                                    if( ($hasSchedEndsOnOne) && ($finalTimeStart == $finalOne) ){
                                        continue;
                                    }

                                    $hasSchedStartsOnOne = Prof_sched::select("*")
                                    ->where("profId", $profId)
                                    ->where("profSchool", $subSchool)
                                    // ->where("subCode", $subCode)
                                    ->where("schedDay", $day)
                                    ->whereTime('startTime', '=', $finalOne)
                                    ->exists();

                                    if( ($hasSchedStartsOnOne) && ($finalTimeEnd == $finalOne) ){
                                        continue;
                                    }

                                    // end
                                    // ///////////////////////////////////////////////////////////////////////////

                                    // finding available room
                                    $rooms = Room::where('roomSchool',$profSchool)
                                                ->where('roomDepartment',$profDept)
                                                ->orderBy("id")->get();

                                    // loop6
                                    foreach($rooms as $room){

                                        $finalClassRoom = $room->roomNumber;
                                        $roomSchool = $room->roomSchool;

                                        $classroomTaken = Room_sched::where('profSchool', $profSchool)
                                                    ->where('roomSchool', $roomSchool)
                                                    ->where('roomNumber', $finalClassRoom)
                                                    ->where('schedDay', $day)
                                                    ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                        $query->where(function ($query1) use ($finalTimeStart) {
                                                                    $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                        ->whereTime('endTime', '>', $finalTimeStart);
                                                                })
                                                                ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                                    $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                        ->whereTime('endTime', '>=', $finalTimeEnd);
                                                                })
                                                                ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                                    $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                        ->whereTime('endTime', '<', $finalTimeEnd);
                                                                });
                                                    })
                                                    ->exists();

                                        if($classroomTaken){
                                            continue;
                                        }else{

                                            // saving schedules
                                            Prof_sched::create([
                                                'profId' => $profId,
                                                'profName' => $profName,
                                                'profSchool' => $profSchool,
                                                'subCode' => $subCode,
                                                'schedDay' => $day,
                                                'startTime' => $finalTimeStart,
                                                'endTime' => $finalTimeEnd,
                                                'studCourse' => $subcourse,
                                                'studYear' => $subyear,
                                                'studSection' => $subsection,
                                                'sem' => $currentSem,
                                                'totalHours' => $superFinalFirstHalfSubUnits,
                                                'classroom' => $finalClassRoom,
                                                'sy' => $schYear,
                                            ]);

                                            Room_sched::create([
                                                'profId' => $profId,
                                                'profName' => $profName,
                                                'profSchool' => $roomSchool,
                                                'roomSchool' => $roomSchool,
                                                'roomNumber' => $finalClassRoom,
                                                'subCode' => $subCode,
                                                'schedDay' => $day,
                                                'startTime' => $finalTimeStart,
                                                'endTime' => $finalTimeEnd,
                                                'studCourse' => $subcourse,
                                                'studYear' => $subyear,
                                                'studSection' => $subsection,
                                                'sem' => $currentSem,
                                                'totalHours' => $superFinalFirstHalfSubUnits,
                                                'sy' => $schYear,
                                            ]);

                                            Stud_sched::create([
                                                'profId' => $profId,
                                                'profName' => $profName,
                                                'schId' => $schId,
                                                'schName' => $roomSchool,
                                                'subCode' => $subCode,
                                                'schedDay' => $day,
                                                'startTime' => $finalTimeStart,
                                                'endTime' => $finalTimeEnd,
                                                'studCourse' => $subcourse,
                                                'studYear' => $subyear,
                                                'studSection' => $subsection,
                                                'sem' => $currentSem,
                                                'totalHours' => $superFinalFirstHalfSubUnits,
                                                'classroom' => $finalClassRoom,
                                                'sy' => $schYear,
                                            ]);
                                            // end

                                            // scheduling the other half of this subject
                                            $days = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

                                            // loop7
                                            foreach ($days as $day) {

                                                if($day == "monday"){
                                                    $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartMon')->exists();
                                                    $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartMon');
                                                    $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndMon');
                                                }elseif($day == "tuesday"){
                                                    $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartTue')->exists();
                                                    $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartTue');
                                                    $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndTue');
                                                }elseif($day == "wednesday"){
                                                    $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartWed')->exists();
                                                    $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartWed');
                                                    $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndWed');
                                                }elseif($day == "thursday"){
                                                    $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartThu')->exists();
                                                    $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartThu');
                                                    $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndThu');
                                                }elseif($day == "friday"){
                                                    $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartFri')->exists();
                                                    $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartFri');
                                                    $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndFri');
                                                }elseif($day == "saturday"){
                                                    $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartSat')->exists();
                                                    $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartSat');
                                                    $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndSat');
                                                }elseif($day == "sunday"){
                                                    $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartSun')->exists();
                                                    $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartSun');
                                                    $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndSun');
                                                }
                    
                                                if(!$available){
                                                    continue;
                                                }

                                                // getting total hours of duty of this prof for this specific day
                                                $unitsToday = Prof_sched::where('profId',$profId)->where('schedDay',$day)->sum('totalHours');
                                                $unitsToday = $unitsToday + $superFinalHalfSubUnits;

                                                // making sure that this prof dont exceed 6 hours of duty in one day
                                                if($unitsToday > 6){
                                                    continue;
                                                }

                                                // getting total class hours for this specific day,course,year,section
                                                $unitsThisDay = Prof_sched::select("*")
                                                                    ->where("profSchool", $subSchool)
                                                                    // ->where("subCode", $subCode)
                                                                    ->where("studCourse", $subcourse)
                                                                    ->where("studYear", $subyear)
                                                                    ->where("studSection", $subsection)
                                                                    ->where("schedDay", $day)
                                                                    ->sum('totalHours');

                                                $totalunitsThisDay = $unitsThisDay + $superFinalHalfSubUnits;

                                                // making sure that this class dont exceed 8 hours of class in one day
                                                if($totalunitsThisDay > 8){
                                                    continue;
                                                }

                                                $prefTimeStartSingleNum = str_replace(array('0',':'), '',$prefTimeStart);
                                                $prefTimeEndSingleNum = str_replace(array('0',':'), '',$prefTimeEnd);
                                                $totalProfFreeTime = $prefTimeEndSingleNum - $prefTimeStartSingleNum;
                                                $iteratorForSched = $totalProfFreeTime - $superFinalHalfSubUnits;

                                                // loop8
                                                for($i = 0; $i <= $iteratorForSched; $i++){

                                                    $timeMover = strtotime("0".$i.":00:00");
                                                    $testTime = strtotime("0".$superFinalHalfSubUnits.":00:00");
                                                    $prefTimeStartGoods = strtotime($prefTimeStart);
                                                    $prefTimeEndGoods = strtotime($prefTimeEnd);

                                                    $initialTimeStart = $prefTimeStartGoods + $timeMover;
                                                    $initialTimeEnd = $initialTimeStart + $testTime;
                                                    $finalTimeStart = date("H:i:s",$initialTimeStart);
                                                    $finalTimeEnd = date("H:i:s",$initialTimeEnd);

                                                    if($finalTimeEnd > $prefTimeEnd){
                                                        continue;
                                                    }

                                                    if($finalTimeStart >= $prefTimeEnd){
                                                        continue;
                                                    }

                                                    // making sure theres no conflict between each subject of this specific day,course,year,section
                                                    $hasConflictWithOtherSub = Prof_sched::select("*")
                                                    ->where("profSchool", $subSchool)
                                                    // ->where("subCode", $subCode)
                                                    ->where("studCourse", $subcourse)
                                                    ->where("studYear", $subyear)
                                                    ->where("studSection", $subsection)
                                                    ->where("schedDay", $day)
                                                    ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                        $query->where(function ($query1) use ($finalTimeStart) {
                                                                    $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                        ->whereTime('endTime', '>', $finalTimeStart);
                                                                })
                                                                ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                                    $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                        ->whereTime('endTime', '>=', $finalTimeEnd);
                                                                })
                                                                ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                                    $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                        ->whereTime('endTime', '<', $finalTimeEnd);
                                                                });
                                                    })
                                                    ->exists();

                                                    if($hasConflictWithOtherSub){
                                                        continue;
                                                    }
                                                    // end

                                                    // making sure theres no conflict between each subject of this specific day and professor
                                                    $hasConflictWithOtherProfSched = Prof_sched::select("*")
                                                    ->where("profId", $profId)
                                                    ->where("profSchool", $subSchool)
                                                    // ->where("subCode", $subCode)
                                                    ->where("schedDay", $day)
                                                    ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                        $query->where(function ($query1) use ($finalTimeStart) {
                                                                    $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                        ->whereTime('endTime', '>', $finalTimeStart);
                                                                })
                                                                ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                                    $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                        ->whereTime('endTime', '>=', $finalTimeEnd);
                                                                })
                                                                ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                                    $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                        ->whereTime('endTime', '<', $finalTimeEnd);
                                                                });
                                                    })
                                                    ->exists();

                                                    if($hasConflictWithOtherProfSched){
                                                        continue;
                                                    }
                                                    // end

                                                    // ///////////////////////////////////////////////////////////////////////////
                                                    // making sure that this professor has proper lunch time either 12-1 or 1-2

                                                    $twelve = strtotime("12:00:00");
                                                    $finalTwelve = date("H:i:s",$twelve);

                                                    $one = strtotime("13:00:00");
                                                    $finalOne = date("H:i:s",$one);

                                                    if( ($finalTimeStart <= $finalTwelve) && ($finalTimeEnd > $finalOne) ){
                                                        continue;
                                                    }

                                                    // if($finalTimeStart >= $prefTimeEnd){
                                                    //     continue;
                                                    // }

                                                    $hasSchedEndsOnTwelve = Prof_sched::select("*")
                                                    ->where("profId", $profId)
                                                    ->where("profSchool", $subSchool)
                                                    // ->where("subCode", $subCode)
                                                    ->where("schedDay", $day)
                                                    ->whereTime('endTime', '=', $finalTwelve)
                                                    ->exists();

                                                    if( ($hasSchedEndsOnTwelve) && ($finalTimeStart == $finalTwelve) ){
                                                        continue;
                                                    }

                                                    $hasSchedEndsOnOne = Prof_sched::select("*")
                                                    ->where("profId", $profId)
                                                    ->where("profSchool", $subSchool)
                                                    // ->where("subCode", $subCode)
                                                    ->where("schedDay", $day)
                                                    ->whereTime('endTime', '=', $finalOne)
                                                    ->exists();

                                                    if( ($hasSchedEndsOnOne) && ($finalTimeStart == $finalOne) ){
                                                        continue;
                                                    }

                                                    $hasSchedStartsOnOne = Prof_sched::select("*")
                                                    ->where("profId", $profId)
                                                    ->where("profSchool", $subSchool)
                                                    // ->where("subCode", $subCode)
                                                    ->where("schedDay", $day)
                                                    ->whereTime('startTime', '=', $finalOne)
                                                    ->exists();

                                                    if( ($hasSchedStartsOnOne) && ($finalTimeEnd == $finalOne) ){
                                                        continue;
                                                    }

                                                    // end
                                                    // ///////////////////////////////////////////////////////////////////////////

                                                    // finding available room
                                                    $rooms = Room::where('roomSchool',$profSchool)
                                                    ->where('roomDepartment',$profDept)
                                                    ->orderBy("id")->get();

                                                    // loop9
                                                    foreach($rooms as $room){

                                                        $finalClassRoom = $room->roomNumber;
                                                        $roomSchool = $room->roomSchool;

                                                        $classroomTaken = Room_sched::where('profSchool', $profSchool)
                                                                    ->where('roomSchool', $roomSchool)
                                                                    ->where('roomNumber', $finalClassRoom)
                                                                    ->where('schedDay', $day)
                                                                    ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                                        $query->where(function ($query1) use ($finalTimeStart) {
                                                                                    $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                                        ->whereTime('endTime', '>', $finalTimeStart);
                                                                                })
                                                                                ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                                                    $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                                        ->whereTime('endTime', '>=', $finalTimeEnd);
                                                                                })
                                                                                ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                                                    $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                                        ->whereTime('endTime', '<', $finalTimeEnd);
                                                                                });
                                                                    })
                                                                    ->exists();

                                                        if($classroomTaken){
                                                            continue;
                                                        }else{

                                                            // saving schedules
                                                            Prof_sched::create([
                                                                'profId' => $profId,
                                                                'profSchool' => $profSchool,
                                                                'subCode' => $subCode,
                                                                'schedDay' => $day,
                                                                'startTime' => $finalTimeStart,
                                                                'endTime' => $finalTimeEnd,
                                                                'studCourse' => $subcourse,
                                                                'studYear' => $subyear,
                                                                'studSection' => $subsection,
                                                                'totalHours' => $superFinalHalfSubUnits,
                                                                'classroom' => $finalClassRoom,
                                                                'profName' => $profName,
                                                                'sem' => $currentSem,
                                                                'sy' => $schYear,
                                                            ]);

                                                            Room_sched::create([
                                                                'profId' => $profId,
                                                                'profSchool' => $roomSchool,
                                                                'roomSchool' => $roomSchool,
                                                                'roomNumber' => $finalClassRoom,
                                                                'subCode' => $subCode,
                                                                'schedDay' => $day,
                                                                'startTime' => $finalTimeStart,
                                                                'endTime' => $finalTimeEnd,
                                                                'studCourse' => $subcourse,
                                                                'studYear' => $subyear,
                                                                'studSection' => $subsection,
                                                                'totalHours' => $superFinalHalfSubUnits,
                                                                'profName' => $profName,
                                                                'sem' => $currentSem,
                                                                'sy' => $schYear,
                                                            ]);

                                                            Stud_sched::create([
                                                                'profId' => $profId,
                                                                'schId' => $schId,
                                                                'schName' => $roomSchool,
                                                                'subCode' => $subCode,
                                                                'schedDay' => $day,
                                                                'startTime' => $finalTimeStart,
                                                                'endTime' => $finalTimeEnd,
                                                                'studCourse' => $subcourse,
                                                                'studYear' => $subyear,
                                                                'studSection' => $subsection,
                                                                'totalHours' => $superFinalHalfSubUnits,
                                                                'classroom' => $finalClassRoom,
                                                                'profName' => $profName,
                                                                'sem' => $currentSem,
                                                                'sy' => $schYear,
                                                            ]);
                                                            // end

                                                            // breaking loops to go back to 2nd loop the subjects loop
                                                            break 7;
                                                            // end

                                                        }

                                                    } // end of loop9


                                                } // end of loop8

                                            } // end of loop7

                                            
                                        }

                                    }

                                }else{

                                    $superFinalHalfSubUnits = 0;

                                    if($finalTimeEnd > $prefTimeEnd){
                                        continue;
                                    }

                                    if($finalTimeStart >= $prefTimeEnd){
                                        continue;
                                    }

                                    // making sure theres no conflict between each subject of this specific day,course,year,section
                                    $hasConflictWithOtherSub = Prof_sched::select("*")
                                    ->where("profSchool", $subSchool)
                                    // ->where("subCode", $subCode)
                                    ->where("studCourse", $subcourse)
                                    ->where("studYear", $subyear)
                                    ->where("studSection", $subsection)
                                    ->where("schedDay", $day)
                                    ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                        $query->where(function ($query1) use ($finalTimeStart) {
                                                    $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                        ->whereTime('endTime', '>', $finalTimeStart);
                                                })
                                                ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                    $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                        ->whereTime('endTime', '>=', $finalTimeEnd);
                                                })
                                                ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                    $query3->whereTime('startTime', '>', $finalTimeStart)
                                                        ->whereTime('endTime', '<', $finalTimeEnd);
                                                });
                                    })
                                    ->exists();

                                    if($hasConflictWithOtherSub){
                                        continue;
                                    }
                                    // end

                                    // making sure theres no conflict between each subject of this specific day and professor
                                    $hasConflictWithOtherProfSched = Prof_sched::select("*")
                                    ->where("profId", $profId)
                                    ->where("profSchool", $subSchool)
                                    // ->where("subCode", $subCode)
                                    ->where("schedDay", $day)
                                    ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                        $query->where(function ($query1) use ($finalTimeStart) {
                                                    $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                        ->whereTime('endTime', '>', $finalTimeStart);
                                                })
                                                ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                    $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                        ->whereTime('endTime', '>=', $finalTimeEnd);
                                                })
                                                ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                    $query3->whereTime('startTime', '>', $finalTimeStart)
                                                        ->whereTime('endTime', '<', $finalTimeEnd);
                                                });
                                    })
                                    ->exists();

                                    if($hasConflictWithOtherProfSched){
                                        continue;
                                    }
                                    // end

                                    // ///////////////////////////////////////////////////////////////////////////
                                    // making sure that this professor has proper lunch time either 12-1 or 1-2

                                    $twelve = strtotime("12:00:00");
                                    $finalTwelve = date("H:i:s",$twelve);

                                    $one = strtotime("13:00:00");
                                    $finalOne = date("H:i:s",$one);

                                    if( ($finalTimeStart <= $finalTwelve) && ($finalTimeEnd > $finalOne) ){
                                        continue;
                                    }

                                    // if($finalTimeStart >= $prefTimeEnd){
                                    //     continue;
                                    // }

                                    $hasSchedEndsOnTwelve = Prof_sched::select("*")
                                    ->where("profId", $profId)
                                    ->where("profSchool", $subSchool)
                                    // ->where("subCode", $subCode)
                                    ->where("schedDay", $day)
                                    ->whereTime('endTime', '=', $finalTwelve)
                                    ->exists();

                                    if( ($hasSchedEndsOnTwelve) && ($finalTimeStart == $finalTwelve) ){
                                        continue;
                                    }

                                    $hasSchedEndsOnOne = Prof_sched::select("*")
                                    ->where("profId", $profId)
                                    ->where("profSchool", $subSchool)
                                    // ->where("subCode", $subCode)
                                    ->where("schedDay", $day)
                                    ->whereTime('endTime', '=', $finalOne)
                                    ->exists();

                                    if( ($hasSchedEndsOnOne) && ($finalTimeStart == $finalOne) ){
                                        continue;
                                    }

                                    $hasSchedStartsOnOne = Prof_sched::select("*")
                                    ->where("profId", $profId)
                                    ->where("profSchool", $subSchool)
                                    // ->where("subCode", $subCode)
                                    ->where("schedDay", $day)
                                    ->whereTime('startTime', '=', $finalOne)
                                    ->exists();

                                    if( ($hasSchedStartsOnOne) && ($finalTimeEnd == $finalOne) ){
                                        continue;
                                    }

                                    // end
                                    // ///////////////////////////////////////////////////////////////////////////

                                    // finding available room
                                    $rooms = Room::where('roomSchool',$profSchool)
                                                ->where('roomDepartment',$profDept)
                                                ->orderBy("id")->get();

                                    // loop6
                                    foreach($rooms as $room){

                                        $finalClassRoom = $room->roomNumber;
                                        $roomSchool = $room->roomSchool;

                                        $classroomTaken = Room_sched::where('profSchool', $profSchool)
                                                    ->where('roomSchool', $roomSchool)
                                                    ->where('roomNumber', $finalClassRoom)
                                                    ->where('schedDay', $day)
                                                    ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                        $query->where(function ($query1) use ($finalTimeStart) {
                                                                    $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                        ->whereTime('endTime', '>', $finalTimeStart);
                                                                })
                                                                ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                                    $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                        ->whereTime('endTime', '>=', $finalTimeEnd);
                                                                })
                                                                ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                                    $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                        ->whereTime('endTime', '<', $finalTimeEnd);
                                                                });
                                                    })
                                                    ->exists();

                                        if($classroomTaken){
                                            continue;
                                        }else{

                                            // saving schedules
                                            Prof_sched::create([
                                                'profId' => $profId,
                                                'profSchool' => $profSchool,
                                                'subCode' => $subCode,
                                                'schedDay' => $day,
                                                'startTime' => $finalTimeStart,
                                                'endTime' => $finalTimeEnd,
                                                'studCourse' => $subcourse,
                                                'studYear' => $subyear,
                                                'studSection' => $subsection,
                                                'totalHours' => $subUnits,
                                                'classroom' => $finalClassRoom,
                                                'profName' => $profName,
                                                'sem' => $currentSem,
                                                'sy' => $schYear,
                                            ]);

                                            Room_sched::create([
                                                'profId' => $profId,
                                                'profSchool' => $roomSchool,
                                                'roomSchool' => $roomSchool,
                                                'roomNumber' => $finalClassRoom,
                                                'subCode' => $subCode,
                                                'schedDay' => $day,
                                                'startTime' => $finalTimeStart,
                                                'endTime' => $finalTimeEnd,
                                                'studCourse' => $subcourse,
                                                'studYear' => $subyear,
                                                'studSection' => $subsection,
                                                'totalHours' => $subUnits,
                                                'profName' => $profName,
                                                'sem' => $currentSem,
                                                'sy' => $schYear,
                                            ]);

                                            Stud_sched::create([
                                                'profId' => $profId,
                                                'schId' => $schId,
                                                'schName' => $roomSchool,
                                                'subCode' => $subCode,
                                                'schedDay' => $day,
                                                'startTime' => $finalTimeStart,
                                                'endTime' => $finalTimeEnd,
                                                'studCourse' => $subcourse,
                                                'studYear' => $subyear,
                                                'studSection' => $subsection,
                                                'totalHours' => $subUnits,
                                                'classroom' => $finalClassRoom,
                                                'profName' => $profName,
                                                'sem' => $currentSem,
                                                'sy' => $schYear,
                                            ]);
                                            // end

                                            break 4;

                                        }

                                    }

                                }

                                $timeMover = strtotime("0".$i.":00:00");
                                $testTime = strtotime("0".$subUnits.":00:00");
                                $prefTimeStartGoods = strtotime($prefTimeStart);
                                $prefTimeEndGoods = strtotime($prefTimeEnd);

                                $initialTimeStart = $prefTimeStartGoods + $timeMover;
                                $initialTimeEnd = $initialTimeStart + $testTime;
                                $finalTimeStart = date("H:i:s",$initialTimeStart);
                                $finalTimeEnd = date("H:i:s",$initialTimeEnd);

                                if( ($i == $iteratorForSched) && ($finalTimeEnd <= $prefTimeEnd) ){

                                    // checking if scheduling is success
                                    $schedSuccess = Prof_sched::where('profId', $profId)
                                    ->where('profSchool', $profSchool)
                                    ->where('subCode', $subCode)
                                    ->where('schedDay', $day)
                                    ->where('studCourse', $subcourse)
                                    ->where('studYear', $subyear)
                                    ->where('studSection', $subsection)
                                    ->exists();

                                    if($schedSuccess){

                                        break 3;

                                    }
                                    // end

                                    // scheduling

                                    $days = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

                                    // loop6
                                    foreach ($days as $day) {
                                        
                                        if($day == "monday"){
                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartMon')->exists();
                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartMon');
                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndMon');
                                        }elseif($day == "tuesday"){
                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartTue')->exists();
                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartTue');
                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndTue');
                                        }elseif($day == "wednesday"){
                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartWed')->exists();
                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartWed');
                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndWed');
                                        }elseif($day == "thursday"){
                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartThu')->exists();
                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartThu');
                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndThu');
                                        }elseif($day == "friday"){
                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartFri')->exists();
                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartFri');
                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndFri');
                                        }elseif($day == "saturday"){
                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartSat')->exists();
                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartSat');
                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndSat');
                                        }elseif($day == "sunday"){
                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartSun')->exists();
                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartSun');
                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndSun');
                                        }

                                        if(!$available){
                                            continue;
                                        }

                                        $firstHalfUnits = 3;

                                        // getting total hours of duty of this prof for this specific day
                                        $unitsToday = Prof_sched::where('profId',$profId)->where('schedDay',$day)->sum('totalHours');
                                        $unitsToday = $unitsToday + $firstHalfUnits;

                                        // making sure that this prof dont exceed 6 hours of duty in one day
                                        if($unitsToday > 6){
                                            continue;
                                        }

                                        // getting total class hours for this specific day,course,year,section
                                        $unitsThisDay = Prof_sched::select("*")
                                                            ->where("profSchool", $subSchool)
                                                            // ->where("subCode", $subCode)
                                                            ->where("studCourse", $subcourse)
                                                            ->where("studYear", $subyear)
                                                            ->where("studSection", $subsection)
                                                            ->where("schedDay", $day)
                                                            ->sum('totalHours');

                                        $totalunitsThisDay = $unitsThisDay + $firstHalfUnits;

                                        // making sure that this class dont exceed 8 hours of class in one day
                                        if($totalunitsThisDay > 8){
                                            continue;
                                        }

                                        $prefTimeStartSingleNum = str_replace(array('0',':'), '',$prefTimeStart);
                                        $prefTimeEndSingleNum = str_replace(array('0',':'), '',$prefTimeEnd);
                                        $totalProfFreeTime = $prefTimeEndSingleNum - $prefTimeStartSingleNum;
                                        $iteratorForSched = $totalProfFreeTime - $firstHalfUnits;

                                        // loop7
                                        for($i = 0; $i <= $iteratorForSched; $i++){

                                            $timeMover = strtotime("0".$i.":00:00");
                                            $testTime = strtotime("0".$firstHalfUnits.":00:00");
                                            $prefTimeStartGoods = strtotime($prefTimeStart);
                                            $prefTimeEndGoods = strtotime($prefTimeEnd);

                                            $initialTimeStart = $prefTimeStartGoods + $timeMover;
                                            $initialTimeEnd = $initialTimeStart + $testTime;
                                            $finalTimeStart = date("H:i:s",$initialTimeStart);
                                            $finalTimeEnd = date("H:i:s",$initialTimeEnd);

                                            if($finalTimeEnd > $prefTimeEnd){
                                                continue;
                                            }

                                            if($finalTimeStart >= $prefTimeEnd){
                                                continue;
                                            }

                                            // making sure theres no conflict between each subject of this specific day,course,year,section
                                            $hasConflictWithOtherSub = Prof_sched::select("*")
                                            ->where("profSchool", $subSchool)
                                            // ->where("subCode", $subCode)
                                            ->where("studCourse", $subcourse)
                                            ->where("studYear", $subyear)
                                            ->where("studSection", $subsection)
                                            ->where("schedDay", $day)
                                            ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                $query->where(function ($query1) use ($finalTimeStart) {
                                                            $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                ->whereTime('endTime', '>', $finalTimeStart);
                                                        })
                                                        ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                            $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                ->whereTime('endTime', '>=', $finalTimeEnd);
                                                        })
                                                        ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                            $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                ->whereTime('endTime', '<', $finalTimeEnd);
                                                        });
                                            })
                                            ->exists();

                                            if($hasConflictWithOtherSub){
                                                continue;
                                            }
                                            // end

                                            // making sure theres no conflict between each subject of this specific day and professor
                                            $hasConflictWithOtherProfSched = Prof_sched::select("*")
                                            ->where("profId", $profId)
                                            ->where("profSchool", $subSchool)
                                            // ->where("subCode", $subCode)
                                            ->where("schedDay", $day)
                                            ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                $query->where(function ($query1) use ($finalTimeStart) {
                                                            $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                ->whereTime('endTime', '>', $finalTimeStart);
                                                        })
                                                        ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                            $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                ->whereTime('endTime', '>=', $finalTimeEnd);
                                                        })
                                                        ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                            $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                ->whereTime('endTime', '<', $finalTimeEnd);
                                                        });
                                            })
                                            ->exists();

                                            if($hasConflictWithOtherProfSched){
                                                continue;
                                            }
                                            // end

                                            // ///////////////////////////////////////////////////////////////////////////
                                            // making sure that this professor has proper lunch time either 12-1 or 1-2

                                            $twelve = strtotime("12:00:00");
                                            $finalTwelve = date("H:i:s",$twelve);

                                            $one = strtotime("13:00:00");
                                            $finalOne = date("H:i:s",$one);

                                            if( ($finalTimeStart <= $finalTwelve) && ($finalTimeEnd > $finalOne) ){
                                                continue;
                                            }

                                            // if($finalTimeStart >= $prefTimeEnd){
                                            //     continue;
                                            // }

                                            $hasSchedEndsOnTwelve = Prof_sched::select("*")
                                            ->where("profId", $profId)
                                            ->where("profSchool", $subSchool)
                                            // ->where("subCode", $subCode)
                                            ->where("schedDay", $day)
                                            ->whereTime('endTime', '=', $finalTwelve)
                                            ->exists();

                                            if( ($hasSchedEndsOnTwelve) && ($finalTimeStart == $finalTwelve) ){
                                                continue;
                                            }

                                            $hasSchedEndsOnOne = Prof_sched::select("*")
                                            ->where("profId", $profId)
                                            ->where("profSchool", $subSchool)
                                            // ->where("subCode", $subCode)
                                            ->where("schedDay", $day)
                                            ->whereTime('endTime', '=', $finalOne)
                                            ->exists();

                                            if( ($hasSchedEndsOnOne) && ($finalTimeStart == $finalOne) ){
                                                continue;
                                            }

                                            $hasSchedStartsOnOne = Prof_sched::select("*")
                                            ->where("profId", $profId)
                                            ->where("profSchool", $subSchool)
                                            // ->where("subCode", $subCode)
                                            ->where("schedDay", $day)
                                            ->whereTime('startTime', '=', $finalOne)
                                            ->exists();

                                            if( ($hasSchedStartsOnOne) && ($finalTimeEnd == $finalOne) ){
                                                continue;
                                            }

                                            // end
                                            // ///////////////////////////////////////////////////////////////////////////

                                            // finding available room
                                            $rooms = Room::where('roomSchool',$profSchool)
                                            ->where('roomDepartment',$profDept)
                                            ->orderBy("id")->get();

                                            // loop8
                                            foreach($rooms as $room){

                                                $finalClassRoom = $room->roomNumber;
                                                $roomSchool = $room->roomSchool;

                                                $classroomTaken = Room_sched::where('profSchool', $profSchool)
                                                            ->where('roomSchool', $roomSchool)
                                                            ->where('roomNumber', $finalClassRoom)
                                                            ->where('schedDay', $day)
                                                            ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                                $query->where(function ($query1) use ($finalTimeStart) {
                                                                            $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                                ->whereTime('endTime', '>', $finalTimeStart);
                                                                        })
                                                                        ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                                            $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                                ->whereTime('endTime', '>=', $finalTimeEnd);
                                                                        })
                                                                        ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                                            $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                                ->whereTime('endTime', '<', $finalTimeEnd);
                                                                        });
                                                            })
                                                            ->exists();

                                                if($classroomTaken){
                                                    continue;
                                                }else{

                                                    // saving schedules
                                                    Prof_sched::create([
                                                        'profId' => $profId,
                                                        'profSchool' => $profSchool,
                                                        'subCode' => $subCode,
                                                        'schedDay' => $day,
                                                        'startTime' => $finalTimeStart,
                                                        'endTime' => $finalTimeEnd,
                                                        'studCourse' => $subcourse,
                                                        'studYear' => $subyear,
                                                        'studSection' => $subsection,
                                                        'totalHours' => $firstHalfUnits,
                                                        'classroom' => $finalClassRoom,
                                                        'profName' => $profName,
                                                        'sem' => $currentSem,
                                                        'sy' => $schYear,
                                                    ]);

                                                    Room_sched::create([
                                                        'profId' => $profId,
                                                        'profSchool' => $roomSchool,
                                                        'roomSchool' => $roomSchool,
                                                        'roomNumber' => $finalClassRoom,
                                                        'subCode' => $subCode,
                                                        'schedDay' => $day,
                                                        'startTime' => $finalTimeStart,
                                                        'endTime' => $finalTimeEnd,
                                                        'studCourse' => $subcourse,
                                                        'studYear' => $subyear,
                                                        'studSection' => $subsection,
                                                        'totalHours' => $firstHalfUnits,
                                                        'profName' => $profName,
                                                        'sem' => $currentSem,
                                                        'sy' => $schYear,
                                                    ]);

                                                    Stud_sched::create([
                                                        'profId' => $profId,
                                                        'schId' => $schId,
                                                        'schName' => $roomSchool,
                                                        'subCode' => $subCode,
                                                        'schedDay' => $day,
                                                        'startTime' => $finalTimeStart,
                                                        'endTime' => $finalTimeEnd,
                                                        'studCourse' => $subcourse,
                                                        'studYear' => $subyear,
                                                        'studSection' => $subsection,
                                                        'totalHours' => $firstHalfUnits,
                                                        'classroom' => $finalClassRoom,
                                                        'profName' => $profName,
                                                        'sem' => $currentSem,
                                                        'sy' => $schYear,
                                                    ]);
                                                    // end

                                                    // scheduling 2nd half of this subject

                                                    $days = array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");

                                                    // loop9
                                                    foreach ($days as $day) {
                                                        
                                                        if($day == "monday"){
                                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartMon')->exists();
                                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartMon');
                                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndMon');
                                                        }elseif($day == "tuesday"){
                                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartTue')->exists();
                                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartTue');
                                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndTue');
                                                        }elseif($day == "wednesday"){
                                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartWed')->exists();
                                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartWed');
                                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndWed');
                                                        }elseif($day == "thursday"){
                                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartThu')->exists();
                                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartThu');
                                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndThu');
                                                        }elseif($day == "friday"){
                                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartFri')->exists();
                                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartFri');
                                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndFri');
                                                        }elseif($day == "saturday"){
                                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartSat')->exists();
                                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartSat');
                                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndSat');
                                                        }elseif($day == "sunday"){
                                                            $available = Prof_config::where('profId', $profId)->whereNotNull('prefTimeStartSun')->exists();
                                                            $prefTimeStart = Prof_config::where('profId', $profId)->value('prefTimeStartSun');
                                                            $prefTimeEnd = Prof_config::where('profId', $profId)->value('prefTimeEndSun');
                                                        }

                                                        if(!$available){
                                                            continue;
                                                        }

                                                        $secondHalfUnits = 2;

                                                        // getting total hours of duty of this prof for this specific day
                                                        $unitsToday = Prof_sched::where('profId',$profId)->where('schedDay',$day)->sum('totalHours');
                                                        $unitsToday = $unitsToday + $secondHalfUnits;

                                                        // making sure that this prof dont exceed 6 hours of duty in one day
                                                        if($unitsToday > 6){
                                                            continue;
                                                        }

                                                        // getting total class hours for this specific day,course,year,section
                                                        $unitsThisDay = Prof_sched::select("*")
                                                                            ->where("profSchool", $subSchool)
                                                                            // ->where("subCode", $subCode)
                                                                            ->where("studCourse", $subcourse)
                                                                            ->where("studYear", $subyear)
                                                                            ->where("studSection", $subsection)
                                                                            ->where("schedDay", $day)
                                                                            ->sum('totalHours');

                                                        $totalunitsThisDay = $unitsThisDay + $secondHalfUnits;

                                                        // making sure that this class dont exceed 8 hours of class in one day
                                                        if($totalunitsThisDay > 8){
                                                            continue;
                                                        }

                                                        $prefTimeStartSingleNum = str_replace(array('0',':'), '',$prefTimeStart);
                                                        $prefTimeEndSingleNum = str_replace(array('0',':'), '',$prefTimeEnd);
                                                        $totalProfFreeTime = $prefTimeEndSingleNum - $prefTimeStartSingleNum;
                                                        $iteratorForSched = $totalProfFreeTime - $secondHalfUnits;

                                                        // loop10
                                                        for($i = 0; $i <= $iteratorForSched; $i++){

                                                            $timeMover = strtotime("0".$i.":00:00");
                                                            $testTime = strtotime("0".$secondHalfUnits.":00:00");
                                                            $prefTimeStartGoods = strtotime($prefTimeStart);
                                                            $prefTimeEndGoods = strtotime($prefTimeEnd);

                                                            $initialTimeStart = $prefTimeStartGoods + $timeMover;
                                                            $initialTimeEnd = $initialTimeStart + $testTime;
                                                            $finalTimeStart = date("H:i:s",$initialTimeStart);
                                                            $finalTimeEnd = date("H:i:s",$initialTimeEnd);

                                                            if($finalTimeEnd > $prefTimeEnd){
                                                                continue;
                                                            }

                                                            if($finalTimeStart >= $prefTimeEnd){
                                                                continue;
                                                            }

                                                            // making sure theres no conflict between each subject of this specific day,course,year,section
                                                            $hasConflictWithOtherSub = Prof_sched::select("*")
                                                            ->where("profSchool", $subSchool)
                                                            // ->where("subCode", $subCode)
                                                            ->where("studCourse", $subcourse)
                                                            ->where("studYear", $subyear)
                                                            ->where("studSection", $subsection)
                                                            ->where("schedDay", $day)
                                                            ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                                $query->where(function ($query1) use ($finalTimeStart) {
                                                                            $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                                ->whereTime('endTime', '>', $finalTimeStart);
                                                                        })
                                                                        ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                                            $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                                ->whereTime('endTime', '>=', $finalTimeEnd);
                                                                        })
                                                                        ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                                            $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                                ->whereTime('endTime', '<', $finalTimeEnd);
                                                                        });
                                                            })
                                                            ->exists();

                                                            if($hasConflictWithOtherSub){
                                                                continue;
                                                            }
                                                            // end

                                                            // making sure theres no conflict between each subject of this specific day and professor
                                                            $hasConflictWithOtherProfSched = Prof_sched::select("*")
                                                            ->where("profId", $profId)
                                                            ->where("profSchool", $subSchool)
                                                            // ->where("subCode", $subCode)
                                                            ->where("schedDay", $day)
                                                            ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                                $query->where(function ($query1) use ($finalTimeStart) {
                                                                            $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                                ->whereTime('endTime', '>', $finalTimeStart);
                                                                        })
                                                                        ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                                            $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                                ->whereTime('endTime', '>=', $finalTimeEnd);
                                                                        })
                                                                        ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                                            $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                                ->whereTime('endTime', '<', $finalTimeEnd);
                                                                        });
                                                            })
                                                            ->exists();

                                                            if($hasConflictWithOtherProfSched){
                                                                continue;
                                                            }
                                                            // end

                                                            // ///////////////////////////////////////////////////////////////////////////
                                                            // making sure that this professor has proper lunch time either 12-1 or 1-2

                                                            $twelve = strtotime("12:00:00");
                                                            $finalTwelve = date("H:i:s",$twelve);

                                                            $one = strtotime("13:00:00");
                                                            $finalOne = date("H:i:s",$one);

                                                            if( ($finalTimeStart <= $finalTwelve) && ($finalTimeEnd > $finalOne) ){
                                                                continue;
                                                            }

                                                            // if($finalTimeStart >= $prefTimeEnd){
                                                            //     continue;
                                                            // }

                                                            $hasSchedEndsOnTwelve = Prof_sched::select("*")
                                                            ->where("profId", $profId)
                                                            ->where("profSchool", $subSchool)
                                                            // ->where("subCode", $subCode)
                                                            ->where("schedDay", $day)
                                                            ->whereTime('endTime', '=', $finalTwelve)
                                                            ->exists();

                                                            if( ($hasSchedEndsOnTwelve) && ($finalTimeStart == $finalTwelve) ){
                                                                continue;
                                                            }

                                                            $hasSchedEndsOnOne = Prof_sched::select("*")
                                                            ->where("profId", $profId)
                                                            ->where("profSchool", $subSchool)
                                                            // ->where("subCode", $subCode)
                                                            ->where("schedDay", $day)
                                                            ->whereTime('endTime', '=', $finalOne)
                                                            ->exists();

                                                            if( ($hasSchedEndsOnOne) && ($finalTimeStart == $finalOne) ){
                                                                continue;
                                                            }

                                                            $hasSchedStartsOnOne = Prof_sched::select("*")
                                                            ->where("profId", $profId)
                                                            ->where("profSchool", $subSchool)
                                                            // ->where("subCode", $subCode)
                                                            ->where("schedDay", $day)
                                                            ->whereTime('startTime', '=', $finalOne)
                                                            ->exists();

                                                            if( ($hasSchedStartsOnOne) && ($finalTimeEnd == $finalOne) ){
                                                                continue;
                                                            }

                                                            // end
                                                            // ///////////////////////////////////////////////////////////////////////////

                                                            // finding available room
                                                            $rooms = Room::where('roomSchool',$profSchool)
                                                            ->where('roomDepartment',$profDept)
                                                            ->orderBy("id")->get();

                                                            // loop11
                                                            foreach($rooms as $room){

                                                                $finalClassRoom = $room->roomNumber;
                                                                $roomSchool = $room->roomSchool;

                                                                $classroomTaken = Room_sched::where('profSchool', $profSchool)
                                                                            ->where('roomSchool', $roomSchool)
                                                                            ->where('roomNumber', $finalClassRoom)
                                                                            ->where('schedDay', $day)
                                                                            ->where(function ($query) use ($finalTimeStart,$finalTimeEnd) {
                                                                                $query->where(function ($query1) use ($finalTimeStart) {
                                                                                            $query1->whereTime('startTime', '<=', $finalTimeStart)
                                                                                                ->whereTime('endTime', '>', $finalTimeStart);
                                                                                        })
                                                                                        ->orWhere(function ($query2) use ($finalTimeEnd) {
                                                                                            $query2->whereTime('startTime', '<', $finalTimeEnd)
                                                                                                ->whereTime('endTime', '>=', $finalTimeEnd);
                                                                                        })
                                                                                        ->orWhere(function ($query3) use ($finalTimeStart,$finalTimeEnd) {
                                                                                            $query3->whereTime('startTime', '>', $finalTimeStart)
                                                                                                ->whereTime('endTime', '<', $finalTimeEnd);
                                                                                        });
                                                                            })
                                                                            ->exists();

                                                                if($classroomTaken){
                                                                    continue;
                                                                }else{

                                                                    // saving schedules
                                                                    Prof_sched::create([
                                                                        'profId' => $profId,
                                                                        'profSchool' => $profSchool,
                                                                        'subCode' => $subCode,
                                                                        'schedDay' => $day,
                                                                        'startTime' => $finalTimeStart,
                                                                        'endTime' => $finalTimeEnd,
                                                                        'studCourse' => $subcourse,
                                                                        'studYear' => $subyear,
                                                                        'studSection' => $subsection,
                                                                        'totalHours' => $secondHalfUnits,
                                                                        'classroom' => $finalClassRoom,
                                                                        'profName' => $profName,
                                                                        'sem' => $currentSem,
                                                                        'sy' => $schYear,
                                                                    ]);

                                                                    Room_sched::create([
                                                                        'profId' => $profId,
                                                                        'profSchool' => $roomSchool,
                                                                        'roomSchool' => $roomSchool,
                                                                        'roomNumber' => $finalClassRoom,
                                                                        'subCode' => $subCode,
                                                                        'schedDay' => $day,
                                                                        'startTime' => $finalTimeStart,
                                                                        'endTime' => $finalTimeEnd,
                                                                        'studCourse' => $subcourse,
                                                                        'studYear' => $subyear,
                                                                        'studSection' => $subsection,
                                                                        'totalHours' => $secondHalfUnits,
                                                                        'profName' => $profName,
                                                                        'sem' => $currentSem,
                                                                        'sy' => $schYear,
                                                                    ]);

                                                                    Stud_sched::create([
                                                                        'profId' => $profId,
                                                                        'schId' => $schId,
                                                                        'schName' => $roomSchool,
                                                                        'subCode' => $subCode,
                                                                        'schedDay' => $day,
                                                                        'startTime' => $finalTimeStart,
                                                                        'endTime' => $finalTimeEnd,
                                                                        'studCourse' => $subcourse,
                                                                        'studYear' => $subyear,
                                                                        'studSection' => $subsection,
                                                                        'totalHours' => $secondHalfUnits,
                                                                        'classroom' => $finalClassRoom,
                                                                        'profName' => $profName,
                                                                        'sem' => $currentSem,
                                                                        'sy' => $schYear,
                                                                    ]);
                                                                    // end

                                                                    break 9;

                                                                }

                                                            }


                                                        }

                                                    } // end of loop9

                                                    //end

                                                }

                                            } // end of loop8


                                        } // end of loop7
                                        

                                    } // end of loop6

                                    // end
                                    
                                }

                            }

                        } //end of foreach loop for days

                    }else{
                        continue;
                    }

                    // checking if scheduling is success
                    $schedSuccess = Prof_sched::where('profId', $profId)
                    ->where('profSchool', $profSchool)
                    ->where('subCode', $subCode)
                    ->where('studCourse', $subcourse)
                    ->where('studYear', $subyear)
                    ->where('studSection', $subsection)
                    ->exists();

                    if(!$schedSuccess){
                        // record this concern, there is no available prof or room for this specific course, year, section, subject
                    }
                    // end

                }

            }

        }

        Toast::title('Generating Schedule Done!')
        ->success()
        ->rightTop()
        ->backdrop()
        ->autoDismiss(1.5);

        return redirect()->back();
    }

}
