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
        'end_date' => 'datetime'
    ];
}
