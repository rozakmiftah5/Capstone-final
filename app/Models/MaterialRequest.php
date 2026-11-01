<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialRequest extends Model
{
    protected $fillable = [
        'request_number',
        'user_id',
        'letter_number',
        'requesting_division',
        'requesting_department',
        'request_date',
        'status',
        'read_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'read_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
