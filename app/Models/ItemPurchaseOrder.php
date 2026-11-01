<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemPurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'item_purchase_orders';
    protected $primaryKey = 'item_po_id';

    protected $fillable = [
        'po_id', 'material_id', 'qty', 'unit', 'unit_price', 'total_price'
    ];

    // Relasi ke Purchase Order
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id', 'po_id');
    }

    // Relasi ke Material
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id', 'material_id');
    }
}
