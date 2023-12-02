<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = "order";

    protected $primaryKey = "id_order";

    public $timestamps = true;
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'id_order', 'id_order');
    }

    public function variants()
    {
        return $this->hasManyThrough(ShoeVariant::class, OrderDetail::class, 'id_order', 'id_variant', 'id_order', 'id_variant');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'id_order');
    }

    protected $fillable = [
        'id_customer',
        'name_buyer',
        'phone_number',
        'address',
        'note',
        'total',
        'payment',
        'status',
        'id_staff',
        'payment_id'
    ];
}
