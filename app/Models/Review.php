<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    protected $table = "review";

    protected $primaryKey = "id_review";

    public $timestamps = true;
    public function order()
{
    return $this->belongsTo(Order::class, 'id_order');
}

    protected $fillable = [
        'id_order',
        'id_customer',
        'id_variant',
        'rated',
        'comment'
    ];
}
