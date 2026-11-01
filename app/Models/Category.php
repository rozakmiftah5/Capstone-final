<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Menentukan nama tabel di database secara eksplisit
    protected $table = 'categories';

    // Nonaktifkan timestamps jika tabel categories tidak memiliki kolom created_at dan updated_at
    public $timestamps = false;

    // Kolom yang boleh diisi (mass assignment)
    protected $fillable = ['name'];

    /**
     * Relasi ke Model Material
     * Satu kategori bisa dimiliki oleh banyak material
     */
    public function materials()
    {
        return $this->hasMany(Material::class, 'category', 'name');
    }
}