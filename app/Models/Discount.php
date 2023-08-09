<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;
    protected $table = "discount";

    protected $primaryKey = "id_discount";

    public $timestamps = false;

    protected $fillable = [
        'name_discount',
        'discount_value',
        'id_staff'
    ];
}
