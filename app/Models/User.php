<?php

namespace App\Models;

use App\Helpers\ChangeLogger;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_url',
        'unit_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'new_passcode'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'allowed_currencies' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        self::updating(function ($model) {

            if ($model->client && $model->client->status == "Approved") {

                // log changes for approval if user can't make the change
                if (!Common::canUserMakeChange() && Auth::check()) {
                    $url = url("/client-management/clients/" . $model->client->type . "/edit/" . $model->client->id);
                    ChangeLogger::log($model, "User details updated", $url);
                    return false;
                }
            }
        });
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'client_id');
    }

    public function manager()
    {
        return $this->hasOne(User::class, 'id', 'manager_id');
    }

    public function beneficiaries()
    {
        return $this->hasMany(ClientBeneficiary::class, 'user_id', 'id');
    }

    public function linkClients()
    {
        return $this->hasMany(ClientUser::class, 'user_id', 'id');
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class, 'user_id', 'id');
    }

    public function linkedClients()
    {
        return $this->belongsToMany(Client::class, 'client_users', 'user_id', 'client_id');
    }

    public function unit()
    {
        return $this->hasOne(Unit::class, 'id', 'unit_id');
    }
}
