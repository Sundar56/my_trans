<?php

namespace App\Api\Frontend\Modules\Transpact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiHistory extends Model
{
    use HasFactory;

    protected $table = "apihistory";
    protected $fillable = [
        'customer_id',
        'apiurl',
        'apimethod',
        'request_payload',
        'response_payload',
        'apimode',
    ];
}
