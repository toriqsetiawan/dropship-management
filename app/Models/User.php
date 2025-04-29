<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'parent_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the parent user (distributor) of this user.
     */
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get the child users (resellers) of this user.
     */
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Check if user is a product owner
     */
    public function isProductOwner()
    {
        return $this->role === 'product_owner';
    }

    /**
     * Check if user is a distributor
     */
    public function isDistributor()
    {
        return $this->role === 'distributor';
    }

    /**
     * Check if user is a reseller
     */
    public function isReseller()
    {
        return $this->role === 'reseller';
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Get all resellers under this user (recursive)
     */
    public function getAllResellers()
    {
        $resellers = collect();

        if ($this->isDistributor()) {
            $resellers = $this->children()->where('role', 'reseller')->get();
        } elseif ($this->isProductOwner()) {
            $distributors = $this->children()->where('role', 'distributor')->get();
            foreach ($distributors as $distributor) {
                $resellers = $resellers->merge($distributor->children()->where('role', 'reseller')->get());
            }
        }

        return $resellers;
    }
}
