<?php

namespace App\Api\Frontend\Modules\Dispute\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisputeChat extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = "disputechat";
    protected $fillable = [
        'from_id',
        'to_id',
        'project_id',
        'body',
        'seen',
        'dispute_id',
        'is_edited',
        'is_replied',
        'replied_id',
        'is_forwarded',
        'is_saved',
        'no_of_attachment',
    ];
}
