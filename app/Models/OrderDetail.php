<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
    protected $table = 'orderdetail';
    protected $primaryKey = ['id_order', 'id_variant'];
    public $incrementing = false;
    public $timestamps = false;
    public function order()
    {
        return $this->belongsTo(Order::class, 'id_order', 'id_order');
    }

    public function shoeVariant()
    {
        return $this->belongsTo(ShoeVariant::class, 'id_variant');
    }

    protected $fillable = [
        'id_order',
        'id_variant',
        'quantity',
        'cur_price'
    ];
}
