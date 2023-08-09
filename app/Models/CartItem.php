<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;
    protected $table = 'cart_item';
    protected $primaryKey = ['id_variant', 'id_customer'];
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id_variant',
        'id_customer',
        'quantity',
    ];
}
