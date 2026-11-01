<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';
    protected $primaryKey = 'supplier_id';

    protected $fillable = ['supplier_code', 'suppllier_name'];

    // Relasi ke Purchase Order
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id', 'supplier_id');
    }

    public function materialReceipts(): HasMany
    {
        return $this->hasMany(MaterialReceipt::class, 'supplier_id', 'supplier_id');
    }
}
