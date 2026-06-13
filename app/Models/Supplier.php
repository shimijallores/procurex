<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Supplier extends Model
{
    use Searchable;

    protected $fillable = [
        'name',
        'proprietor',
        'authorized_representative',
        'owner',
        'contact_person',
        'contact_number',
        'email',
        'address',
        'tin',
        'remarks',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'proprietor' => $this->proprietor,
            'contact_person' => $this->contact_person,
        ];
    }

    public function masterListItems(): HasMany
    {
        return $this->hasMany(MasterListItem::class);
    }

    public function rfqSuppliers(): HasMany
    {
        return $this->hasMany(RFQSupplier::class, 'supplier_id');
    }

    public function wonAoqs(): HasMany
    {
        return $this->hasMany(AOQ::class, 'winner_supplier_id');
    }
}
