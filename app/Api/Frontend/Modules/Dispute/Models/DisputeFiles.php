<?php

namespace App\Api\Frontend\Modules\Dispute\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DisputeFiles extends Model
{
    use HasFactory;

    protected $table = "disputefiles";
    protected $fillable = [
        'dispute_id',
        'support_material',
    ];
}
