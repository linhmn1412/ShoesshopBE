<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoeVariant extends Model
{
    use HasFactory;

    protected $table = "shoevariant";

    protected $primaryKey = "id_variant";

    public $timestamps = false;

    public function shoe()
    {
        return $this->belongsTo(Shoe::class, 'id_shoe');
    }

    protected $fillable = [
        'id_shoe',
        'size',
        'color',
        'quantity_stock',
        'quantity_sold',
    ];
}
