<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Auditable;

class Notification extends Model
{
    use Auditable;
    //
    protected $fillable = [
        'user_id',
        'type',
        'message',
        'is_read',
        'status',
        'deleted_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
