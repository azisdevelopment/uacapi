<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class AttendanceTxn extends Model
{
    use HasFactory,  HasApiTokens;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
    protected $table = 'attendancetxn';
    protected $fillable = [
        'attendanceRef',
        'attendancePresentA',
        'attendancePresentB',
        'attendancePresentC',
        'attendanceStatus',
        'attendanceActive',
        'classtxnautoid',
        'studentpercpsautoid',
        'studentautoid',
    ];
}
