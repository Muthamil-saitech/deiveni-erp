<?php

namespace App\Models;

use Database\Factories\WorkerCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerCategory extends Model
{
    /** @use HasFactory<WorkerCategoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name_ta',
        'name_en',
        'code',
        'daily_wage',
        'shift',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_wage' => 'decimal:2',
        ];
    }
}
