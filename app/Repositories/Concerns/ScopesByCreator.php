<?php

namespace App\Repositories\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopesByCreator
{
    /**
     * Restrict the query to records created by the authenticated user unless they are an admin.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function scopeByCreator(Builder $query, string $column = 'created_by'): Builder
    {
        $user = Auth::user();

        if ($user && ! $user->isAdmin()) {
            $query->where($column, $user->id);
        }

        return $query;
    }
}
