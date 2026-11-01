<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialRequestItem extends Model
{
    protected $fillable = [
        'material_request_id',
        'material_description',
        'details',
        'sap_material_code',
    ];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id');
    }
}
