<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_seeder_removes_stale_grants_from_limited_roles(): void
    {
        Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $regionalAdmin = Role::create(['name' => 'admin_wilayah', 'display_name' => 'Admin Wilayah']);
        $driver = Role::create(['name' => 'supir', 'display_name' => 'Supir']);
        $staleBookingPermission = Permission::create(['name' => 'booking.view', 'display_name' => 'Lihat Booking']);
        $regionalAdmin->permissions()->attach($staleBookingPermission->id);
        $driver->permissions()->attach($staleBookingPermission->id);

        $this->seed(PermissionSeeder::class);

        $regionalAdmin = Role::with('permissions')->where('name', 'admin_wilayah')->firstOrFail();
        $driver = Role::with('permissions')->where('name', 'supir')->firstOrFail();
        $admin = Role::with('permissions')->where('name', 'admin')->firstOrFail();

        $this->assertFalse($regionalAdmin->hasPermission('booking.view'));
        $this->assertTrue($regionalAdmin->hasPermission('fleet-position.update-assigned-city'));
        $this->assertFalse($driver->hasPermission('booking.view'));
        $this->assertTrue($driver->hasPermission('fleet-position.report-own-trip'));
        $this->assertFalse($admin->hasPermission('users.manage'));
        $this->assertFalse($admin->hasPermission('roles-permissions.manage'));
        $this->assertTrue($admin->hasPermission('booking.create'));
    }
}
