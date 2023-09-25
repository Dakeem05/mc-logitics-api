<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentNaira1 extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $cast = [
        'start_date' => 'datetime',
        'is_usdt' => 'boolean',
        'end_date' => 'datetime'
    ];
}
