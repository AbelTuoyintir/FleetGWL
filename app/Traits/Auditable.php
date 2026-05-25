<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the Auditable trait.
     * Automatically sets created_by on creating and modified_by on updating.
     */
    public static function bootAuditable(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && in_array('created_by', $model->getFillable())) {
                $model->created_by = $model->created_by ?? Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check() && in_array('modified_by', $model->getFillable())) {
                $model->modified_by = Auth::id();
            }
        });
    }

    /**
     * Soft delete the model by setting status to 'deleted' and recording who deleted it.
     */
    public function softDelete(): bool
    {
        $attributes = ['status' => 'deleted'];

        if (in_array('deleted_by', $this->getFillable()) && Auth::check()) {
            $attributes['deleted_by'] = Auth::id();
        }

        return $this->update($attributes);
    }
}
