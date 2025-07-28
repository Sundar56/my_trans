<?php

namespace App\Api\Frontend\Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectTasks extends Model
{
    use HasFactory;

    const PENDING    = '0';
    const ONGOING    = '1';
    const COMPLETED  = '2';

    protected $table = "projecttasks";
    protected $fillable = [
        'project_id',
        'taskname',
        'taskamount',
        'tasknotes',
        'taskstatus',
        'is_verified',
    ];
}
