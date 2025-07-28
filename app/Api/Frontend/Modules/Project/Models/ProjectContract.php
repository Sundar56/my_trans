<?php

namespace App\Api\Frontend\Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectContract extends Model
{
    use HasFactory;

    protected $table = "projectcontract";
    protected $fillable = [
        'user_id',
        'project_id',
        'contract',
    ];
}
