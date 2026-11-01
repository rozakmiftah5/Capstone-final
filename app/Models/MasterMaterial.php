<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterMaterial extends Model
{
    protected $fillable = [
        'material_code',
        'short_text',
        'uom',
        'material_group',
        'material_type',
        'valuation_class',
    ];
}
