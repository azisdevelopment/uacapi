<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\RequestException;
use Illuminate\Database\QueryException;

use App\Models\User;
use App\Models\Lecturer;
use App\Models\Otp;
use App\Models\Account;


use PDO;
use PDOException;

class AuthController extends Controller
{
    //
    public function register(Request $request) {

        $fields = $request->validate([
            'name' => 'required',
            'email' => 'required',
            'password' => 'required|confirmed',
        ]);

        $user = User::create($fields);

        $token = $user->createToken($request->email);
        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function login(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            return ['message' => 'The provided credential are incorrect.'];
        }
        
        $token = $user->createToken($user->name);
        return [
            'user'  => $user,
            'token' => $token->plainTextToken
        ];

    }

    public function logout(Request $request) {
        $request->user()->tokens()->delete();
        return ['message' => 'You are logged out.'];
    }


    public function accountlogin(Request $request) {
        $resObj = [];
        $request->validate([
            'lecturerId' => 'required',
            'lecturerEmail' => 'required',
        ]);

        try{
            //STEP 1: Check lecturer credential
            $lecturer = Lecturer::where('lecturerId', $request->lecturerId)
                    ->where('lecturerEmail', $request->lecturerEmail)
                    ->where('lecturerActive', '=', 1)
                    ->first();
            
            if(!$lecturer){
                $resObj = $this->responseObj('CR-01', 'The provided credential are incorrect.');
                return response()->json($resObj, 200);
            }

            //STEP 2: Check Account Terminated
            $account = Account::where('accountId', $request->lecturerId)
                    ->first();
            
            if($account){
                if($account->accountActive !== 1 ){
                    $resObj = $this->responseObj('CR-01', 'Account has been terminated.');
                    return response()->json($resObj, 200);
                }
            }

            //STEP 3: Generate OTP
            $otpCode = '';
            for ($i = 0; $i < 4; $i++) { $otpCode .= rand(0, 9); }

            //STEP 3: Create OTP
            $otpCreate = $this->createOtp($otpCode, $lecturer->id);

            //STEP 4: Send Otp Code to Email

            
            $resData = [];
            array_push($resData, (object)[
                'otpautoid' => $otpCreate->id,
                'lecturerautoid' => $lecturer->id,
            ]);

            array_push($resObj, (object)[
                'responseCode' => 'CR-0010',
                'responseMsg' => 'Success',
                'responseData' => $resData
            ]);

            return response()->json($resObj, 200);

        }catch (QueryException | PDOException $e) {
            array_push($resObj, (object)[
                'responseCode' => 'CR-00',
                'responseMsg' => 'Exception Error',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }

    }

    public function accountloginotp(Request $request) {
        $resObj = [];
        $request->validate([
            'otpCode' => 'required',
            'otpautoid' => 'required',
            'lecturerautoid' => 'required'
        ]);

        try{
            //STEP 1: Verify OTP
            $otp = Otp::where('id', $request->otpautoid)
                    ->where('lecturerautoid', $request->lecturerautoid)
                    ->where('otpStatus', '=', 1)
                    ->where('otpAttempt', '<', 3)
                    ->first();

            if(!$otp){
                $resObj = $this->responseObj('CR-0021', 'OTP code reached the maximum number of attempts!');
                return response()->json($resObj, 200);
            }else{
                //STEP 1.1: Cross Check OTP Code
                if($otp->otpCode === $request->otpCode ){

                    //STEP 1.2: Update OTP Status Completed
                    $otpStatusUpdate = Otp::where('id', $request->otpautoid)
                    ->update(['otpStatus' => 0, 'otpAttempt' => 10,]);
                    
                }else{
                    //STEP 1.3: Update OTP Attempt
                    $otpAttemptUpdate = $this->updateOtpAttempt($request->otpautoid, $otp->otpAttempt);
                    $resObj = $this->responseObj('CR-0022', 'OTP Code incorrect! '. (3 - ($otp->otpAttempt + 1)).' attempts left.');
                    return response()->json($resObj, 200);
                }
            }



            $lecturer = Lecturer::where('id', $request->lecturerautoid)
                    ->first();
            
            $resData = [];
            array_push($resData, (object)[
                'lecturerautoid' => $request->lecturerautoid,
                'lecturerId' => $lecturer->lecturerId,
            ]);

            array_push($resObj, (object)[
                'responseCode' => 'CR-0020',
                'responseMsg' => 'Success',
                'responseData' => $resData
            ]);

            return response()->json($resObj, 200);

        }catch (QueryException | PDOException $e) {
            array_push($resObj, (object)[
                'responseCode' => 'CR-0029',
                'responseMsg' => 'Exception Error',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }
    }

    //=========================================================================
    //PRIVATE FUNCTION DATABASE INSERT / UPDATE

    private function createOtp(string $otpCode, int $lecturerAutoId): Otp
    {
        return Otp::create([
            'otpCode' => $otpCode,
            'otpAttempt' => 0,
            'otpStatus' => 1,
            'lecturerautoid' => $lecturerAutoId
        ]);
    }

    private function updateOtpAttempt(int $otpautoid, int $otpAttempt): int
    {
        return Otp::where('id', $otpautoid)->update(['otpAttempt' => $otpAttempt + 1 ]);
    }

    private function responseObj(string $responseCode, string $responseMsg)
    {
        return [[
            'responseCode' => $responseCode,
            'responseMsg' => $responseMsg,
            'responseData' => ''
        ]];
    }
}
