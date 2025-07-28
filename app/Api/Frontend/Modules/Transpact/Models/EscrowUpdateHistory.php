<?php

namespace App\Api\Frontend\Modules\Transpact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EscrowUpdateHistory extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = "escrow_updatehistory";
    protected $fillable = [
        'updated_by',
        'previous_record',
        'updated_record',
        'updated_time',
        'ipaddress',
        'useragent',
    ];
}
