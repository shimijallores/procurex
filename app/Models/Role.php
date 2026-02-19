<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'office_id',
    ];

    protected function casts(): array
    {
        return [
            'is_system_role' => 'boolean',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if this is a system role.
     */
    public function isSystemRole(): bool
    {
        return $this->is_system_role || RoleType::isSystemRole($this->name);
    }

    /**
     * Scope to get only office roles (non-system).
     */
    public function scopeOfficeRoles($query)
    {
        return $query->where('is_system_role', false);
    }

    /**
     * Scope to get only system roles.
     */
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }
}
