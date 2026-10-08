<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\HasRolesAndPermissions;

#[Fillable(['name', 'email', 'password', 'assigned_city_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRolesAndPermissions, Notifiable;

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

    public function landingRouteName(): string
    {
        $destinations = [
            'dashboard' => ['dashboard.view'],
            'booking.fleet-condition' => ['fleet-condition.view-all', 'fleet-condition.view-assigned-trips', 'fleet-condition.view-own-trips'],
            'booking' => ['booking.view'],
            'booking.trips' => ['trip.view'],
            'packages' => ['packages.manage'],
            'cities' => ['master-data.manage'],
            'route-fares' => ['route-fare.manage'],
            'booking.settings' => ['booking.settings.manage'],
            'users' => ['users.manage'],
            'roles-permissions' => ['roles-permissions.manage'],
        ];

        foreach ($destinations as $routeName => $permissions) {
            if ($this->hasPermission($permissions)) {
                return $routeName;
            }
        }

        return 'home';
    }

    public function assignedCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'assigned_city_id');
    }
}
