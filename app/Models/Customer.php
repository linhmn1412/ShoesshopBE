<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $table = "customer";

    protected $primaryKey = "id_customer";

    public $timestamps = false;
    public function user()
{
    return $this->belongsTo(User::class, 'id_customer', 'id_user');
}

// Trong model User
public function customer()
{
    return $this->hasOne(Customer::class, 'id_customer', 'id_user');
}

    protected $fillable = ['point'];
}
