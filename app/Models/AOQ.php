<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AOQ extends Model
{
    protected $table = 'aoqs';

    protected $fillable = [
        'rfq_id',
        'aoq_date',
        'winner_supplier_id',
    ];

    protected $casts = [
        'aoq_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(RFQ::class, 'rfq_id');
    }

    public function winnerSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'winner_supplier_id');
    }

    public function bacResolution(): HasOne
    {
        return $this->hasOne(BACResolution::class, 'aoq_id');
    }
}
