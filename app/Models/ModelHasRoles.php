<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModelHasRoles extends Model
{
    use HasFactory;

    public const ADMIN = 1;
    public const CUSTOMER = 2;
    public const CONTRACTOR = 3;

    protected $table = "model_has_roles";
    protected $fillable = [
        'role_id',
        'module_type',
        'module_id'
    ];
    public $timestamps = false;
}
