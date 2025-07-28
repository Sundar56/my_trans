<?php

namespace App\Api\Frontend\Modules\Dispute\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dispute extends Model
{
    use HasFactory;

    protected $table = "dispute";
    protected $fillable = [
        'project_id',
        'created_by',
        'reason',
        'sent_to',
    ];
}
