<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoginHistory extends Model
{
    use HasFactory;

    protected $table = "loginhistory";
    protected $fillable = [
        'user_id',
        'logintime',
        'logouttime',
        'duration',
        'ipaddress',
        'useragent',
    ];
}
