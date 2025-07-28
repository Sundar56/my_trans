<?php

namespace App\Api\Frontend\Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invitation extends Model
{
    protected $table = "invitation";
    protected $fillable = [
        'invitefrom',
        'inviteto',
        'invitemail',
        'invitestatus',
        'project_id',
        'reinvite',
    ];
}
