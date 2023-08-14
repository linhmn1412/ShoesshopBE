<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;
    protected $table = "staff";

    protected $primaryKey = "id_staff";

    public $timestamps = false;
    protected $casts = [
        'status' => 'boolean',
    ];

    protected $fillable = ["id_staff",'salary','status'];
}
