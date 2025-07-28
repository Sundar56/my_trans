<?php

namespace App\Api\Frontend\Modules\Dispute\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatAttachment extends Model
{
      use HasFactory;

    protected $table = "chat_attachment";
    protected $fillable = [
        'disputechat_id',
        'filename',
        'filetype',
    ];
}
