<?php

namespace App\Api\Frontend\Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectDecline extends Model
{
    use HasFactory;

    protected $table = "projectdecline";
    protected $fillable = [
        'project_id',
        'customer_id',
        'contractor_id',
        'reason',
        'status',
    ];
}
