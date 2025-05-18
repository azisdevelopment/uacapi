<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory,  HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';
    protected $table = 'course';
    protected $fillable = [
        'courseCode',
        'courseName',
        'courseDesc',
        'courseStatus',
        'courseActive',
        'siteautoid',
    ];
}
