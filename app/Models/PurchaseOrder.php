<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';
    protected $primaryKey = 'po_id';

    protected $fillable = ['po_number', 'po_date', 'supplier_id'];

    // Relasi ke Supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    // Relasi ke Item Purchase Order
    public function items()
    {
        return $this->hasMany(ItemPurchaseOrder::class, 'po_id', 'po_id');
    }
}