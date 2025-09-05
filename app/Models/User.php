<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'user_type_id',
        'image',
        'phone',
        'sales_partner_id',
        'overwrite_base_price',
        'overwrite_panel_price',
        'email_preference',
    ];

    protected $with = ['roles'];

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
    ];

    public function type()
    {
        return $this->belongsTo(UserType::class, "user_type_id", "id");
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, "id", "user_id");
    }

    public function scopeFilterByRole($query, $rolename)
    {
        return $query->whereHas("roles", function ($query) use ($rolename) {
            $query->where("name", $rolename);
        });
    }

    public function canImpersonate(): bool
    {
        return $this->hasRole('Super Admin');
    }
    public function canBeImpersonated(): bool
    {
        return !$this->hasRole('Super Admin');
    }

    // public function notifications()
    // {
    //     return $this->hasMany(Notification::class, 'notifiable_id', 'id')->where('notifiable_type', 'App\Models\User');
    // }
    // public function unreadNotifications()
    // {
    //     return $this->hasMany(Notification::class, 'notifiable_id', 'id')->whereNull('read_at');
    // }
}
