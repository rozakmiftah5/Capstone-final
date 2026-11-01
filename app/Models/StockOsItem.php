<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOsItem extends Model
{
    protected $fillable = [
        'material_name',
        'warehouse_name',
        'stock_qty',
        'outstanding_qty',
        'safety_stock',
        'notes',
        'last_updated',
    ];

    protected function casts(): array
    {
        return [
            'stock_qty' => 'decimal:3',
            'outstanding_qty' => 'decimal:3',
            'safety_stock' => 'decimal:3',
            'last_updated' => 'date',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }
}
