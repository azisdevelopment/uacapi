<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\mobile\AuthController;
use App\Http\Controllers\mobile\AccountController;
use App\Http\Controllers\mobile\HomeController;

use App\Http\Controllers\mobile\LoadController;
use App\Http\Controllers\mobile\LoginController;
use App\Http\Controllers\mobile\OtpController;


//use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('posts', PostController::class);
// Route::get('/', function (){
//     return 'API';
// });

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


//LOAD ROUTES
Route::post('/mobile/load/servertime', [LoadController::class, 'getServerTime']);
Route::post('/mobile/load/accountverify', [LoadController::class, 'accountverify']);

//LOGIN ROUTES
Route::post('/mobile/login/accountverify', [LoginController::class, 'accountverify']);
Route::post('/mobile/login/accountlogin', [LoginController::class, 'accountlogin']);

Route::post('/mobile/accountlogin', [AuthController::class, 'accountlogin']);
Route::post('/mobile/accountloginotp', [AuthController::class, 'accountloginotp']);

//LOGIN OTP ROUTES
Route::post('/mobile/otp/accountloginotp', [OtpController::class, 'accountloginotp']);
Route::post('/mobile/otp/accountregister', [OtpController::class, 'accountregister']);

//ACCOUNT
Route::post('/mobile/accountregister', [AccountController::class, 'accountregister']);
Route::post('/mobile/accountverify', [AccountController::class, 'accountverify']);
Route::post('/mobile/servertime', [AccountController::class, 'getServerTime']);

//HOME
Route::post('/mobile/currentsession', [HomeController::class, 'currentsession']);
Route::post('/mobile/currentdatetime', [HomeController::class, 'currentdatetime']);
Route::post('/mobile/todayschedule', [HomeController::class, 'todayschedule']);
Route::post('/mobile/todayliveschedule', [HomeController::class, 'todayliveschedule']);
Route::post('/mobile/todaylivescheduleattendance', [HomeController::class, 'todaylivescheduleattendance']);
Route::post('/mobile/studentcoursesession', [HomeController::class, 'studentcoursesession']);
Route::post('/mobile/saveattendancetxn', [HomeController::class, 'saveattendancetxn']);
