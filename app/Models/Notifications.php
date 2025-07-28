<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notifications extends Model
{
    use HasFactory;

    protected $table = "notifications";
    protected $fillable = [
        'from_id',
        'to_id',
        'project_id',
        'message',
        'isread',
        'status',
    ];
}
