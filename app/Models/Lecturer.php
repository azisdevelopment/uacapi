<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;


class Lecturer extends Model
{
    use HasFactory,  HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
    protected $table = 'lecturer';
    protected $fillable = [
        'lecturerId',
        'lecturerFirstName',
        'lecturerLastName',
        'lecturerEmail',
        'lecturerTelNo',
        'lecturerStatus',
        'lecturerActive',
        'siteautoid',
    ];
}
