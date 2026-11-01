<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialReceipt extends Model
{
    protected $fillable = [
        'material_id',
        'material_code',
        'material_name',
        'warehouse_id',
        'supplier_id',
        'supplier_name',
        'received_by',
        'quantity',
        'unit',
        'received_at',
        'purchase_order_number',
        'delivery_note_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id', 'material_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }
}
