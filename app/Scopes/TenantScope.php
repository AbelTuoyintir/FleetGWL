<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('tenant_id')) {
            $builder->where($model->getTable() . '.company_id', app('tenant_id'));
        } elseif (Auth::check() && !Auth::user()->isAdmin()) {
             // Fallback for non-admins if middleware didn't set it yet but user is logged in
             // Usually middleware handles this, but global scope is a second layer.
             $builder->where($model->getTable() . '.company_id', Auth::user()->company_id);
        }
    }
}
