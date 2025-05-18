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
use App\Models\Otp;


use PDO;
use PDOException;

class LoginController extends Controller
{
    public function accountverify(Request $request) {
        $resObj = [];
        $request->validate([
            'accountId' => 'required',
            'accountautoid' => 'required',
            'token' => 'required'
        ]);

        try{

            //STEP 1: Check Account exist
            $account = Account::where('id', $request->accountautoid)
                    ->where('accountId', $request->accountId)
                    ->where('accountActive', '=', 1)
                    ->first();

            if(!$account){
                //return
                //type of data return is empty{}
                $resObj = $this->responseObj('CR-01', 'Account Inactive / does not exist');
                return response()->json($resObj, 200);
            }

            //STEP 2: Update Token Expiredate

            array_push($resObj, (object)[
                'responseCode' => 'CR-0040',
                'responseMsg' => 'Success',
                'responseData' => $account
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

    private function responseObj(string $responseCode, string $responseMsg)
    {
        return [[
            'responseCode' => $responseCode,
            'responseMsg' => $responseMsg,
            'responseData' => ''
        ]];
    }
    
}
