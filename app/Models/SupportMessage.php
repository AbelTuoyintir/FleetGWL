<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = ['support_chat_id', 'sender_type', 'message'];

    public function chat()
    {
        return $this->belongsTo(SupportChat::class, 'support_chat_id');
    }
}
