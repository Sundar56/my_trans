<?php

namespace App\Api\Common\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookHistory extends Model
{
    use HasFactory;

    protected $table = "webhookhistory";
    protected $fillable = [
        'customer_email',
        'contractor_email',
        'transactionid',
        'eventid',
        'amount',
        'istest',
        'payload'
    ];
}
