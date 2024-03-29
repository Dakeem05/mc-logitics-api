<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\Api\V1\ApiResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'ref_code',
        'referer_code',
        'team_earning',
        'has_invested',
        'username',
        'user_bank_name',
        'email_verified_at',
        'bank_name',
        'role',
        'account_number',
        'asset_password',
        'usdt_balance',
        'naira_balance',
        'email',
        'transfer_recipient',
        'transfer_code',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'has_invested' => 'boolean',
    ];

    public function forgot_otp ():HasOne
    {
        return $this->hasOne(ForgotPasswordOtp::class);
    }

    public function withdraw ():HasOne
    {
        return $this->hasOne(Withdraw::class, 'user_id', 'id');
    }

    public function sendApiEmailForgotPasswordNotification()
    {
    //    $this->hasOne(ForgotPasswordOtp::class);
       $this->notify(new ApiResetPassword);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
