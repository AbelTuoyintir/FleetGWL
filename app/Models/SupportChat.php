<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportChat extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = ['user_id', 'session_id', 'subject', 'status', 'company_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class);
    }
}
