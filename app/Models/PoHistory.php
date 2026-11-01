<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoHistory extends Model
{
    protected $fillable = [
        'po_number',
        'po_date',
        'material_code',
        'short_text',
        'material_group',
        'vendor',
        'qty',
        'uom',
        'currency',
        'unit_price',
        'total_price',
        'source_key',
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'qty' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }
}
