<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Menentukan nama tabel secara eksplisit
    protected $table = 'users';

    // Menyesuaikan primary key dengan struktur tabel Anda
    protected $primaryKey = 'user_id';

    // Kolom yang boleh diisi (mass assignment)
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
    ];

    // Kolom yang disembunyikan saat data dikonversi ke array/JSON
    protected $hidden = [
        'password',
    ];
}