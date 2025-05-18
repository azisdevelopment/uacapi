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

class LoadController extends Controller
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
}
