<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleMenu extends Model
{
    use HasFactory;

    protected $table = 'role_menus';

    protected $fillable = [
        'role_id',
        'menu_key',
    ];

    /**
     * Get the role associated with this menu permission.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
