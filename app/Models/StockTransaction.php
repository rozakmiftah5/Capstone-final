<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransaction extends Model
{
    protected $fillable = [
        'stock_os_item_id',
        'user_id',
        'type',
        'qty',
        'supplier',
        'transaction_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'transaction_date' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StockOsItem::class, 'stock_os_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
