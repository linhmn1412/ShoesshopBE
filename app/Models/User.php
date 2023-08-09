<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = "user";

    protected $primaryKey = "id_user";

    public $timestamps = false;
    
    protected $fillable = [
        'fullname', 
        'birth',
        'gender',
        'email', 
        'phone_number',  
        'address',
        'username', 
        'password',
        'id_role',
    ];

}
