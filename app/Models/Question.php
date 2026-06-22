<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['module_id', 'question_text', 'options', 'correct_answer'];

    protected $casts = [
        'options' => 'array'
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
