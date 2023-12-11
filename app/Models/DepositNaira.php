<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositNaira extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts =[
        'is_usdt' => 'boolean',
    ];
}
