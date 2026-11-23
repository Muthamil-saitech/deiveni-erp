<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /**
     * Get the menu permissions granted to this role.
     */
    public function menus(): HasMany
    {
        return $this->hasMany(RoleMenu::class);
    }

    /**
     * Get the users assigned to this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the role's slug as value (for compatibility with existing role checks).
     */
    public function getValueAttribute(): string
    {
        return $this->slug ?? Str::slug($this->name);
    }

    /**
     * Get the human-readable display label.
     */
    public function label(): string
    {
        return match (strtolower($this->slug ?? '')) {
            'owner', 'admin' => 'Owner (Admin)',
            default => $this->name,
        };
    }

    /**
     * Get the Tamil label for known roles or default.
     */
    public function labelTa(): string
    {
        return match (str_replace(['-', ' '], '_', strtolower(trim($this->slug ?? '')))) {
            'owner', 'admin' => 'உரிமையாளர் (நிர்வாகி)',
            'site_manager' => 'தள மேலாளர்',
            'account_manager' => 'கணக்கு மேலாளர்',
            default => $this->name,
        };
    }

    /**
     * Get badge CSS classes for UI display.
     */
    public function badgeClass(): string
    {
        return match (str_replace(['-', ' '], '_', strtolower(trim($this->slug ?? '')))) {
            'owner', 'admin' => 'bg-primary text-white',
            'site_manager' => 'bg-warning text-dark',
            'account_manager' => 'bg-success text-white',
            default => 'bg-info text-dark',
        };
    }

    /**
     * Check if this role has permission for a specific menu key.
     */
    public function hasMenu(string $menuKey): bool
    {
        if ($this->relationLoaded('menus')) {
            return $this->menus->pluck('menu_key')->contains($menuKey);
        }

        return $this->menus()->where('menu_key', $menuKey)->exists();
    }

    /**
     * Synchronize granted menu permissions for this role.
     *
     * @param  array<int, string>  $menuKeys
     */
    public function syncMenus(array $menuKeys): void
    {
        $this->menus()->delete();

        foreach (array_unique($menuKeys) as $key) {
            if (! empty($key)) {
                $this->menus()->create([
                    'menu_key' => $key,
                ]);
            }
        }

        $this->unsetRelation('menus');
    }
}
