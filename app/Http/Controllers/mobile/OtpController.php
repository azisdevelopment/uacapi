<?php

namespace App\Http\Controllers\mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\RequestException;
use Illuminate\Database\QueryException;


use App\Models\Account;
use App\Models\AccountPerMobile;
use App\Models\Otp;
use App\Models\Lecturer;


use PDO;
use PDOException;

class OtpController extends Controller
{
    public function accountloginotp(Request $request) {
        $resObj = [];
        $request->validate([
            'otpCode' => 'required',
            'otpautoid' => 'required',
            'lecturerautoid' => 'required'
        ]);

        try{
            //STEP 1: Verify OTP
            /*
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
            */

            $otp = Otp::where('id', $request->otpautoid)
                ->where('lecturerautoid', $request->lecturerautoid)
                ->first();

            if(!$otp){
                $resObj = $this->responseObj('CR-01', 'Invalid Request! Data Does Not Exists');
                return response()->json($resObj, 200);
            }

            if($otp->otpStatus != 1  ){
                $resObj = $this->responseObj('CR-22', 'OTP Code is not valid anymore!');
                return response()->json($resObj, 200);
            }

            if($otp->updateOtpAttempt >= 3 ){
                $resObj = $this->responseObj('CR-22', 'OTP code reached the maximum number of attempts!');
                return response()->json($resObj, 200);
            }

            

            //STEP 1.1: Cross Check OTP Code
            if($otp->otpCode !== $request->otpCode ){
                //STEP 1.3: Update OTP Attempt
                $otpAttemptUpdate = $this->updateOtpAttempt($request->otpautoid, $otp->otpAttempt);
                if($otp->otpAttempt + 1 == 3){
                    $resObj = $this->responseObj('CR-0022', 'OTP code reached the maximum number of attempts!');
                }else{
                    $resObj = $this->responseObj('CR-0021', 'OTP Code incorrect! '. (3 - ($otp->otpAttempt + 1)).' attempts left.');
                }
                
                return response()->json($resObj, 200);
            }

            //STEP 1.2: Update OTP Status Completed
            $otpStatusUpdate = Otp::where('id', $request->otpautoid)
            ->update(['otpStatus' => 0, 'otpAttempt' => 10,]);

            $lecturer = Lecturer::where('id', $request->lecturerautoid)->first();
            
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
                'responseCode' => 'CR-00',
                'responseMsg' => 'Exception Error',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }
    }

    public function accountregister(Request $request) {
        $resObj = [];
        $request->validate([
            'lecturerId' => 'required',
            'lecturerautoid' => 'required',
            'mobileautoid' => 'required'
        ]);

        try{
            //STEP 1: Check Account exist
            $account = Account::where('accountId', $request->lecturerId)
                    ->where('lecturerautoid', $request->lecturerautoid)
                    ->first();
            
            
            if(!$account){
                //STEP 1.1: CREATE ACCOUNT
                $accountCreate = $this->createAccount($request->lecturerId, $request->lecturerautoid);
 
                //STEP 1.2: CreateLINK Account Mobile
                $accountMobileCreate = $this->createAccountPerMobile($accountCreate->id, $request->mobileautoid);
                
                //STEP 1.3: Create Token


                $dataObj = [];
                array_push($dataObj, (object)[
                    'account' => $accountCreate,
                    'token' => 'token',
                ]);
                //return
                array_push($resObj, (object)[
                    'responseCode' => 'CR-0031',
                    'responseMsg' => 'Create Account / Account Mobile / Token',
                    'responseData' => $dataObj,
                ]);
    
                return response()->json($resObj, 200);
            }

            //STEP 2: Check AccountMobile exist
            $accountpermobile = AccountPerMobile::where('accountautoid', $account->id)
                ->where('mobileautoid', $request->mobileautoid)
                ->first();

            if(!$accountpermobile){
                //STEP 2.1: Create Account Mobile
                $accountMobileCreate = $this->createAccountPerMobile($account->id, $request->mobileautoid);

                //STEP 2.2: Create Token


                $dataObj = [];
                array_push($dataObj, (object)[
                    'account' => $account,
                    'token' => 'token',
                ]);
                //return
                array_push($resObj, (object)[
                    'responseCode' => 'CR-0032',
                    'responseMsg' => 'Create Account Mobile / Token',
                    'responseData' => $dataObj,
                ]);
    
                return response()->json($resObj, 200);
            }
            

            
            //STEP 3: Create Token

            $dataObj = [];
            array_push($dataObj, (object)[
                'account' => $account,
                'token' => 'token',
            ]);

            array_push($resObj, (object)[
                'responseCode' => 'CR-0030',
                'responseMsg' => 'Success',
                'responseData' => $dataObj
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

    //=========================================================================
    //PRIVATE FUNCTION DATABASE INSERT / UPDATE

    
    private function updateOtpAttempt(int $otpautoid, int $otpAttempt): int
    {
        return Otp::where('id', $otpautoid)->update(['otpAttempt' => $otpAttempt + 1 ]);
    }

    private function createAccount(string $lecturerId, int $lecturerAutoId): Account
    {
        return Account::create([
            'accountId'      => $lecturerId,
            'accountStatus'  => 1, // or use a constant
            'accountActive'  => 1,
            'lecturerautoid' => $lecturerAutoId
        ]);
    }

    private function createAccountPerMobile(int $accountautoid, int $mobileautoid): AccountPerMobile
    {
        return AccountPerMobile::create([
            'accountautoid' => $accountautoid,
            'mobileautoid' => $mobileautoid
        ]);
        
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
