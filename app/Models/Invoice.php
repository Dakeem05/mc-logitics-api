<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts =[
        'isPositive ' => 'boolean',
        'date' => 'datetime',
        'is_usdt' => 'boolean',
    ];
}
