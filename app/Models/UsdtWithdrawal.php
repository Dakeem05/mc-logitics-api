<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsdtWithdrawal extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $cast = [
        'is_sent' => 'boolean',
        'is_verified' => 'boolean',
    ];
}
