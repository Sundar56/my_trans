<?php

namespace App\Api\Frontend\Modules\Transpact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EscrowSettings extends Model
{
     use HasFactory,SoftDeletes;

    protected $table = "escrowsettings";
    protected $fillable = [
        'name',
        'description',
        'username',
        'password',
        'soapurl',
        'webhookurl',
        'deductionfee',
        'status',
    ];
}
