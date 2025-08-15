<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use App\Models\Room;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //    public function rooms(): BelongsToMany
    //    {
    //        return $this->belongsToMany(Room::class);
    //    }
    //
    //    public function getTenants(Panel $panel): Collection
    //    {
    //        return $this->rooms;
    //    }
    //
    //    public function canAccessTenant(Model $tenant): bool
    //    {
    //        return $this->rooms()->whereKey($tenant)->exists();
    //    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Check if the environment is demo and the user is the demo user
        if (App::environment('demo')) {
            return $this->email === 'demo@example.com';
        }

        // Check if the panel ID is 'admin' and the user has the 'lecturer' role
        if ($panel->getId() === 'admin' && $this->hasRole('lecturer')) {
            return false;
        }

        // Check if the user has the super admin role
        if ($this->hasRole(config('filament-shield.super_admin.name'))) {
            return true;
        }

        // Check if the panel ID is 'lecturer' and the user has the 'lecturer' role
        if ($panel->getId() === 'lecturer' && $this->hasRole('lecturer')) {
            return true;
        }

        // Check if the user has any role other than 'super_admin'
        $allowedRoles = Role::where('name', '!=', 'super_admin')->pluck('name')->toArray();
        return $this->hasAnyRole($allowedRoles);
    }



    public function assessment(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
