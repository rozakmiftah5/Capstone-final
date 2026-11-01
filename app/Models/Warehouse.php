<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';
    protected $primaryKey = 'warehouse_id';

    protected $fillable = ['warehouse_code', 'warehouse_name'];

    // Relasi ke Material
    public function materials()
    {
        return $this->hasMany(Material::class, 'warehouse_id', 'warehouse_id');
    }
}
