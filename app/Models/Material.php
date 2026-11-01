<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $table = 'materials';
    protected $primaryKey = 'material_id';

    protected $fillable = [
        'material_code',
        'material_name',
        'material_type',
        'material_stock',
        'sisa_po',
        'safety_stock',
        'status',
        'category',
        'unit',
        'warehouse_id',
        'valuation_class',
        'note',
    ];

    // Relasi ke Warehouse (Many to One)
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function unit()
{
    return $this->belongsTo(Unit::class, 'unit_id', 'id');
}

    // Relasi ke Item Purchase Order (One to Many)
    public function itemPurchaseOrders()
    {
        return $this->hasMany(ItemPurchaseOrder::class, 'material_id', 'material_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(MaterialReceipt::class, 'material_id', 'material_id');
    }
}