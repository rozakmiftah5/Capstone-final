<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use HasFactory;

    // Menentukan nama tabel di database secara eksplisit
    protected $table = 'types';

    // Nonaktifkan timestamps jika tabel tidak memiliki created_at/updated_at
    public $timestamps = false;

    // Kolom yang boleh diisi
    protected $fillable = ['name'];

    /**
     * Relasi ke Model Material (menghubungkan kolom material_type dengan name)
     */
    public function materials()
    {
        return $this->hasMany(Material::class, 'material_type', 'name');
    }
}