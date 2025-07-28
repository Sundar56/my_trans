<?php

namespace App\Api\Frontend\Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    const PENDING   = '0';
    const APPROVED  = '1';
    const ONGOING   = '2';
    const DEPOSITED = '3';
    const COMPLETED = '4';
    const DISPUTE   = '5';
    const VOID      = '6';
    const ALL       = '7';
    const DECLINE   = '8';
    const REINVITE  = '9';
    const VERIFIED  = '10';
    const VERIFY    = '11';
    const PARTIAL   = '12';
    const FULL      = '13';
    const RELEASED  = '14';
    const LIVE      = '15';
    const DRAFT     = '16';

    protected $table = "projects";
    protected $fillable = [
        'contractor_id',
        'customer_id',
        'projectname',
        'customer_email',
        'projectamount',
        'projectstatus',
        'project_type',
        'currency',
        'conditions',
        'agreement',
        'startdate',
        'completiondate',
        'projectlocation',
        'admincommission',
        'is_create',
        'customer_sign',
        'contractor_sign',
        'escrowfund',
        'status',
        'balancefund',
        'customer_name',
        'contractor_acceptance',
    ];

    public function tasks()
    {
        return $this->hasMany(ProjectTasks::class, 'project_id');
    }
}
