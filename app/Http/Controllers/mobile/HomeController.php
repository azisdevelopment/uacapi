<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\RequestException;
use Illuminate\Database\QueryException;


use App\Models\Account;
use App\Models\Lecturer;
use App\Models\Schedules;
use App\Models\Session;
use App\Models\Room;
use App\Models\SchedulePerRoom;
use App\Models\ScheduleTime;
use App\Models\Course;
use App\Models\Groups;
use App\Models\ClassTxn;
use App\Models\StudentPerCps;
use App\Models\AttendanceTxn;
use App\Models\ClassTxnHistory;


use PDO;
use PDOException;
class HomeController extends Controller
{

    public function currentdatetime(Request $request)
    {   
        $resObj = [];
        try{
            $result = DB::select("SELECT NOW() as now  ");

            if(!$result){
                $resObj = $this->responseObj('CR-0002', 'Database Connection Error!');
                return response()->json($resObj, 200);
            }
            
            array_push($resObj, (object)[
                'responseCode' => 'CR-0001',
                'responseMsg' => 'Success',
                'responseData' => $result
            ]);

            return response()->json($resObj, 200);
        }catch (QueryException | PDOException $e) {
            array_push($resObj, (object)[
                'responseCode' => 'CR-0009',
                'responseMsg' => 'Database Connection Error!',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }

    }

    public function todayschedule(Request $request)
    {   
        $resObj = [];
        
        $request->validate([
            'accountId' => 'required',
        ]);

        

        try{
            $timenow = DB::select("SELECT NOW() as now   ");
            $timeday = DB::select("SELECT DATE_FORMAT(NOW(), '%a') as days   ");
            if(!$timeday){
                $resObj = $this->responseObj('CR-1001', 'Invalid Request - Upcoing Schedule C');
                return response()->json($resObj, 200);
            }

            //STEP 1: get current siteautoid from lecturer
            $lecturer = Lecturer::select('id', 'siteautoid')
                ->where('lecturerId', $request->accountId)
                ->first();
            if(!$lecturer){
                $resObj = $this->responseObj('CR-1002', 'Invalid Request - Upcoing Schedule A');
                return response()->json($resObj, 200);
            }
            

            //STEP 2: get current sessionautoid
            $session = Session::select('id')
                ->where('siteautoid', $lecturer->siteautoid)
                ->where('sessionStatus', 1)
                ->where('sessionActive', 1)
                ->first();
            if(!$session){
                $resObj = $this->responseObj('CR-1003', 'Invalid Request - Upcoing Schedule B');
                return response()->json($resObj, 200);
            }


            //STEP 2: get schedule list
            $schedules = Schedules::select('schedules.id', 'coursepersession.courseautoid', 'coursepersession.groupsautoid') //select('schedules.*', 'coursepersession.*')
                ->join('coursepersession', 'coursepersession.id', '=', 'schedules.coursepersessionautoid')
                ->join('lecturerpercps', 'lecturerpercps.coursepersessionautoid', '=', 'coursepersession.id')
                ->where('schedules.schedulesDay', $timeday[0]->days)
                ->where('coursepersession.sessionautoid', $session->id )
                ->where('lecturerpercps.lecturerautoid', $lecturer->id)
                ->get();

            if(!$schedules){
                $resObj = $this->responseObj('CR-1004', 'Invalid Request - Upcoing Schedule C');
                return response()->json($resObj, 200);
            }

            $resData = [];
            foreach ($schedules as $item) {
                
                // "id": 1,
                // "courseautoid": 1,
                // "groupsautoid": 1

                $courseObj = Course::select('id', 'courseCode','courseName')
                    ->where('id', $item->courseautoid)
                    ->where('siteautoid', $lecturer->siteautoid)
                    ->first();

                $groupObj = Groups::select('id', 'groupCode','groupName')
                    ->where('id', $item->groupsautoid)
                    ->where('siteautoid', $lecturer->siteautoid)
                    ->first();

                $roomObj = Room::select('room.id', 'room.roomCode','room.roomName')
                    ->join('scheduleperroom', 'scheduleperroom.roomautoid', '=', 'room.id')
                    ->where('room.siteautoid', $lecturer->siteautoid)
                    ->where('scheduleperroom.scheduleautoid', $item->id)
                    ->first();
                
                $timeObj = ScheduleTime::select('id', 'stStartTime','stEndTime')
                    ->where('scheduleautoid', $item->id)
                    ->first();

                array_push($resData, (object)[
                    'id' => $item->id,
                    'courseautoid' => $item->courseautoid,
                    'groupsautoid' => $item->groupsautoid,
                    'courseObj' => $courseObj,
                    'groupObj' => $groupObj,
                    'roomObj' => $roomObj,
                    'timeObj' => $timeObj,
                ]);
            }


           
            array_push($resObj, (object)[
                'responseCode' => 'CR-1000',
                'responseMsg' => 'Success',
                'responseData' => $resData
            ]);

            return response()->json($resObj, 200);
        }catch (QueryException | PDOException $e) {
            array_push($resObj, (object)[
                'responseCode' => 'CR-0009',
                'responseMsg' => 'Database Connection Error!',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }

    }

    public function todayliveschedule(Request $request)
    {   
        $resObj = [];
        
        $request->validate([
            'accountId' => 'required',
        ]);


        try{
            
            $lecturer = Lecturer::select('id', 'siteautoid')
                ->where('lecturerId', $request->accountId)
                ->first();
            if(!$lecturer){
                $resObj = $this->responseObj('CR-1002', 'Invalid Request - Upcoing Schedule A');
                return response()->json($resObj, 200);
            }

            $lecturerautoid=$lecturer->id;
            $siteautoid=$lecturer->siteautoid;

            $datenow = DB::select("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d') as now   ");
            $timenow = DB::select("SELECT DATE_FORMAT(NOW(), '%H:%i:%s') as now   ");
            
            $classtxn = ClassTxn::select('classtxn.id as classtxnautoid',  'course.courseCode', 'course.courseName', 'groups.groupName',  'room.roomCode', 'room.roomName', 'scheduletime.stStartTime', 'scheduletime.stEndTime')
                ->join('schedules', 'schedules.id', '=', 'classtxn.scheduleautoid')
                ->join('coursepersession', 'coursepersession.id', '=', 'schedules.coursepersessionautoid')
                ->join('course', 'course.id', '=', 'coursepersession.courseautoid')
                ->join('groups', 'groups.id', '=', 'coursepersession.groupsautoid')

                ->join('scheduletime', 'scheduletime.scheduleautoid', '=', 'schedules.id')
                ->join('scheduleperroom', 'scheduleperroom.scheduleautoid', '=', 'schedules.id')
                ->join('room', 'room.id', '=', 'scheduleperroom.roomautoid')
                
                ->where('classtxn.classDate', $datenow[0]->now)
                ->where('schedules.lecturerautoid', $lecturerautoid)
                ->where('scheduletime.stStartTime', '<=', $timenow[0]->now)
                ->where('scheduletime.stEndTime', '>', $timenow[0]->now)
                ->get();


            $resData = [];

            array_push($resObj, (object)[
                'responseCode' => 'CR-1020',
                'responseMsg' => 'Success',
                'responseData' => $classtxn
            ]);

            return response()->json($resObj, 200);
        }catch (QueryException | PDOException $e) {
            array_push($resObj, (object)[
                'responseCode' => 'CR-1029',
                'responseMsg' => 'Database Connection Error!',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }


    }

    public function todaylivescheduleattendance(Request $request)
    {   
        $resObj = [];
        
        $request->validate([
            'accountId' => 'required',
            'classtxnautoid' => 'required',
        ]);


        try{
            
            $lecturer = Lecturer::select('id', 'siteautoid')
                ->where('lecturerId', $request->accountId)
                ->first();
            if(!$lecturer){
                $resObj = $this->responseObj('CR-1002', 'Invalid Request - Upcoing Schedule A');
                return response()->json($resObj, 200);
            }

            $lecturerautoid=$lecturer->id;
            $siteautoid=$lecturer->siteautoid;

            $datenow = DB::select("SELECT DATE_FORMAT(NOW(), '%Y-%m-%d') as now   ");
            $timenow = DB::select("SELECT DATE_FORMAT(NOW(), '%H:%i:%s') as now   ");
            
            $classtxn = ClassTxn::select('classtxn.id as classtxnautoid',  'course.courseCode', 'course.courseName', 'groups.groupName',  'room.roomCode', 'room.roomName', 'scheduletime.stStartTime', 'scheduletime.stEndTime')
                ->join('schedules', 'schedules.id', '=', 'classtxn.scheduleautoid')
                ->join('coursepersession', 'coursepersession.id', '=', 'schedules.coursepersessionautoid')
                ->join('course', 'course.id', '=', 'coursepersession.courseautoid')
                ->join('groups', 'groups.id', '=', 'coursepersession.groupsautoid')

                ->join('scheduletime', 'scheduletime.scheduleautoid', '=', 'schedules.id')
                ->join('scheduleperroom', 'scheduleperroom.scheduleautoid', '=', 'schedules.id')
                ->join('room', 'room.id', '=', 'scheduleperroom.roomautoid')
                
                ->where('classtxn.id', $request->classtxnautoid)
                ->where('classtxn.classDate', $datenow[0]->now)
                ->where('schedules.lecturerautoid', $lecturerautoid)
                ->where('scheduletime.stStartTime', '<=', $timenow[0]->now)
                ->where('scheduletime.stEndTime', '>', $timenow[0]->now)
                ->get();


            $resData = [];

            array_push($resObj, (object)[
                'responseCode' => 'CR-1020',
                'responseMsg' => 'Success',
                'responseData' => $classtxn
            ]);

            return response()->json($resObj, 200);
        }catch (QueryException | PDOException $e) {
            array_push($resObj, (object)[
                'responseCode' => 'CR-1029',
                'responseMsg' => 'Database Connection Error!',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }


    }

    public function currentsession(Request $request)
    {   
        $resObj = [];
        
        $request->validate([
            'accountId' => 'required',
        ]);

        try{

            $lecturer = Lecturer::select('id', 'siteautoid')
                    ->where('lecturerId', $request->accountId)
                    ->first();
            if(!$lecturer){
                $resObj = $this->responseObj('CR-01', 'Lecturer info does not exist');
                return response()->json($resObj, 200);
            }

            $lecturerautoid=$lecturer->id;
            $siteautoid=$lecturer->siteautoid;

            $session = Session::where('siteautoid', $siteautoid)
                ->where('sessionStatus', 1)
                ->where('sessionActive', 1)
                ->first();

            if(!$session){
                $resObj = $this->responseObj('CR-01', 'Session does not exist');
                return response()->json($resObj, 200);
            }

        

            array_push($resObj, (object)[
                'responseCode' => 'CR-1030',
                'responseMsg' => 'Success',
                'responseData' => $session
            ]);

            return response()->json($resObj, 200);
        }catch (QueryException | PDOException $e) {
            array_push($resObj, (object)[
                'responseCode' => 'CR-1039',
                'responseMsg' => 'Database Connection Error!',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }


    }


    public function studentcoursesession(Request $request)
    {   
        $resObj = [];
        
        $request->validate([
            'accountId' => 'required',
            'classtxnautoid' => 'required',
        ]);

        try{

            $attendance = AttendanceTxn::select('attendancetxn.id')
                ->where('classtxnautoid', $request->classtxnautoid)
                ->get();
            if(!$attendance){
                $resObj = $this->responseObj('CR-1041', 'Invalid Request - Attendance');
                return response()->json($resObj, 200);
            }

            if($attendance->count() < 1){
                $studentCourseList = StudentPerCps::select('studentpercps.id as studentpercpsautoid', 'studentpercps.studentautoid', 'student.studentId', 'student.studentFirstName', 'student.studentLastName')
                    ->join('coursepersession', 'coursepersession.id', '=', 'studentpercps.coursepersessionautoid')
                    ->join('schedules', 'schedules.coursepersessionautoid', '=', 'coursepersession.id')
                    ->join('classtxn', 'classtxn.scheduleautoid', '=', 'schedules.id')
                    ->join('student', 'student.id', '=', 'studentpercps.studentautoid')
                    
                    
                    ->where('classtxn.id', $request->classtxnautoid)
                    ->orderBy('student.studentFirstName', 'ASC') 
                    ->get();

                array_push($resObj, (object)[
                    'responseCode' => 'CR-1040',
                    'responseMsg' => 'Success',
                    'responseData' => $studentCourseList
                ]);
                return response()->json($resObj, 200);
            }
           

            $attendance = AttendanceTxn::select('attendancetxn.attendancePresentA', 'attendancetxn.attendancePresentB', 'attendancetxn.attendancePresentC', 'attendancetxn.studentpercpsautoid', 'attendancetxn.studentautoid', 'attendancetxn.classtxnautoid', 'student.studentId', 'student.studentFirstName', 'student.studentLastName')
                ->join('student', 'student.id', '=', 'attendancetxn.studentautoid')    
                ->join('classtxn', 'classtxn.id', '=', 'attendancetxn.classtxnautoid')
                
                
                
                ->where('classtxn.id', $request->classtxnautoid)
                ->orderBy('student.studentFirstName', 'ASC') 
                ->get();

            array_push($resObj, (object)[
                'responseCode' => 'CR-1040',
                'responseMsg' => 'Success',
                'responseData' => $attendance
            ]);

            return response()->json($resObj, 200);
        }catch (QueryException | PDOException $e) {
            array_push($resObj, (object)[
                'responseCode' => 'CR-1049',
                'responseMsg' => 'Database Connection Error!',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }


    }

    

    public function saveattendancetxn(Request $request)
    {   
        $resObj = [];
        
        $request->validate([
            'accountId' => 'required',
            'classtxnautoid' => 'required',
            'attendanceListObj' => 'required',
        ]);

        try{
            $lecturer = Lecturer::select('id', 'siteautoid')
                    ->where('lecturerId', $request->accountId)
                    ->first();
            if(!$lecturer){
                $resObj = $this->responseObj('CR-1052', 'Invalid Request - Upcoing Schedule A');
                return response()->json($resObj, 200);
            }

            $attendance = AttendanceTxn::select('attendancetxn.id')
                ->where('classtxnautoid', $request->classtxnautoid)
                ->get();
            if(!$attendance){
                $resObj = $this->responseObj('CR-1051', 'Invalid Request - Attendance'. $attendance);
                return response()->json($resObj, 200);
            }

            //$attendanceListObj = json_decode($request->attendanceListObj);
            if($attendance->count() < 1){
                //CREATE ATTENDANCE
                
                foreach ($request->attendanceListObj as $item) {
                    $attendanceRef = 'REF' . date('YmdHis') . rand(10, 99);
                    $attendanceCreate = $this->createAttendanceTxn(
                        $attendanceRef, $item['attendancePresentA'], $item['attendancePresentB'], 
                        $item['attendancePresentC'], $item['classtxnautoid'], $item['studentpercpsautoid'], 
                        $item['studentautoid']);
                }

                $txnDetails = "Create Attendance";
                $txnHistory = $this->createClassTxnHistory($txnDetails, $request->classtxnautoid, $lecturer->id);

                array_push($resObj, (object)[
                    'responseCode' => 'CR-1050',
                    'responseMsg' => 'Success - Create Attendance',
                    'responseData' => $txnHistory
                ]);
                return response()->json($resObj, 200);
            }

            //UPDATE ATTENDANCE
            
            
            foreach ($request->attendanceListObj as $item) {
                $attendanceUpdate = $this->updateAttendanceTxn(
                     $item['attendancePresentA'], $item['attendancePresentB'], 
                    $item['attendancePresentC'], $item['classtxnautoid'], $item['studentpercpsautoid'], 
                    $item['studentautoid']);
            }

            $txnDetails = "Update Attendance";
            $txnHistory = $this->createClassTxnHistory($txnDetails, $request->classtxnautoid, $lecturer->id);

            array_push($resObj, (object)[
                'responseCode' => 'CR-1050',
                'responseMsg' => 'Success - Update Attendance',
                'responseData' => $txnHistory
            ]);

            return response()->json($resObj, 200);
        }catch (QueryException | PDOException $e) {
            array_push($resObj, (object)[
                'responseCode' => 'CR-1059',
                'responseMsg' => 'Database Connection Error!',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }


    }

    //=========================================================================
    //PRIVATE FUNCTION DATABASE INSERT / UPDATE
    
    private function responseObj(string $responseCode, string $responseMsg)
    {
        return [[
            'responseCode' => $responseCode,
            'responseMsg' => $responseMsg,
            'responseData' => ''
        ]];
    }

    private function createAttendanceTxn(string $attendanceRef, int $attendancePresentA, int $attendancePresentB, int $attendancePresentC, int $classtxnautoid, int $studentpercpsautoid, int $studentautoid): AttendanceTxn
    {
        return AttendanceTxn::create([
            'attendanceRef'      => $attendanceRef,
            'attendancePresentA'      => $attendancePresentA,
            'attendancePresentB'      => $attendancePresentB,
            'attendancePresentC'      => $attendancePresentC,
            'attendanceStatus'      => 1,
            'attendanceActive'      => 1,
            'classtxnautoid'      => $classtxnautoid,
            'studentpercpsautoid'  => $studentpercpsautoid, // or use a constant
            'studentautoid'  => $studentautoid,
        ]);
    }

    private function updateAttendanceTxn( int $attendancePresentA, int $attendancePresentB, int $attendancePresentC, int $classtxnautoid,   int $studentpercpsautoid, int $studentautoid): int
    {
        return AttendanceTxn::where('studentautoid', $studentautoid)
            ->where('classtxnautoid', $classtxnautoid)
            ->where('studentpercpsautoid', $studentpercpsautoid)
            ->update([
                'attendancePresentA' => $attendancePresentA,
                'attendancePresentB' => $attendancePresentB,
                'attendancePresentC' => $attendancePresentC,
            ]);
    }

    private function createClassTxnHistory(string $txnDetails, int $classtxnautoid, int $lecturerautoid): ClassTxnHistory
    {
        return ClassTxnHistory::create([
            'txnDetails'      => $txnDetails,
            'classtxnautoid'      => $classtxnautoid,
            'lecturerautoid'      => $lecturerautoid,
        ]);
    }

}
