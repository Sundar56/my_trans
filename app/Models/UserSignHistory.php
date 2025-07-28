<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSignHistory extends Model
{
    use HasFactory;

    protected $table = "usersignhistory";
    protected $fillable = [
        'userid',
        'updatedsign',
    ];
}
