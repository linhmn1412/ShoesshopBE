<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shoe extends Model
{
    use HasFactory;

    protected $table = "shoe";

    protected $primaryKey = "id_shoe";

    public $timestamps = true;

    protected $fillable = [
        'name_shoe',
        'id_category',
        'id_brand',
        'description',
        'price',
        'image',
        'id_discount',
        'id_staff',
    ];
}
