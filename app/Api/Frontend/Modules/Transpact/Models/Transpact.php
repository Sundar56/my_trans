<?php

namespace App\Api\Frontend\Modules\Transpact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transpact extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "transpact";
    protected $fillable = [
        'customer_id',
        'project_id',
        'transpactnumber',
        'status',
    ];

}
