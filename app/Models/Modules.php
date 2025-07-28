<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Modules extends Model
{
    use HasFactory;

    protected $table = "modules";
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'order_id',
        'type',
        'frontend_slug',
    ];
}
