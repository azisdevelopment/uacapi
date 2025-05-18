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


use PDO;
use PDOException;

class AccountController extends Controller
{   
    public function getServerTime(Request $request)
    {   
        $resObj = [];
        try{
            $result = DB::select("SELECT NOW()  ");

            if(!$result){
                $resObj = $this->responseObj('CR-01', 'Database Connection Error!');
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
                'responseCode' => 'CR-00',
                'responseMsg' => 'Database Connection Error!',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }

    }
    
    public function accountverify(Request $request) {
        $resObj = [];
        $request->validate([
            'accountId' => 'required',
            'accountautoid' => 'required'
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
                    'account' => $account,
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
                'responseCode' => 'CR-0039',
                'responseMsg' => 'Exception Error',
                'responseData' => $e
            ]);

            return response()->json($resObj, 200);
        }
    }


    //=========================================================================
    //PRIVATE FUNCTION DATABASE INSERT / UPDATE
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
