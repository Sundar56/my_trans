<?php

namespace App\Api\Frontend\Modules\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $table = "businessprofile";
    protected $fillable = [
        'user_id',
        'businessname',
        'businesstype',
        'businessemail',
        'address',
        'businessphone',
        'businessimage',
        'company_registernum',
    ];
}
