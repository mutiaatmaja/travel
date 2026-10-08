<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $roleName): User
    {
        foreach (['superadmin', 'admin', 'admin_wilayah', 'supir', 'pemilik'] as $name) {
            Role::create(['name' => $name, 'display_name' => $name]);
        }
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->create();
        $user->addRole($roleName);

        return $user;
    }

    #[TestWith(['superadmin', 'dashboard'])]
    #[TestWith(['admin', 'dashboard'])]
    #[TestWith(['pemilik', 'dashboard'])]
    #[TestWith(['admin_wilayah', 'booking.fleet-condition'])]
    #[TestWith(['supir', 'booking.fleet-condition'])]
    public function test_successful_login_redirects_to_page_accessible_to_role(string $roleName, string $routeName): void
    {
        $user = $this->createUserWithRole($roleName);

        Livewire::test('pages::public.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route($routeName));

        $this->assertAuthenticatedAs($user);
        $this->get(route($routeName))->assertOk();
    }

    #[TestWith(['admin', 'dashboard'])]
    #[TestWith(['admin_wilayah', 'booking.fleet-condition'])]
    #[TestWith(['supir', 'booking.fleet-condition'])]
    public function test_authenticated_user_opening_login_is_redirected_to_accessible_page(string $roleName, string $routeName): void
    {
        $this->actingAs($this->createUserWithRole($roleName));

        $this->get(route('login'))->assertRedirect(route($routeName));
    }

    #[TestWith(['booking.view', 'booking'])]
    #[TestWith(['trip.view', 'booking.trips'])]
    #[TestWith(['packages.manage', 'packages'])]
    #[TestWith(['master-data.manage', 'cities'])]
    #[TestWith(['route-fare.manage', 'route-fares'])]
    #[TestWith(['booking.settings.manage', 'booking.settings'])]
    #[TestWith(['users.manage', 'users'])]
    #[TestWith(['roles-permissions.manage', 'roles-permissions'])]
    public function test_login_uses_direct_permission_without_requiring_a_role(string $permissionName, string $routeName): void
    {
        $user = User::factory()->create();
        $user->givePermission(Permission::create(['name' => $permissionName, 'display_name' => $permissionName]));

        Livewire::test('pages::public.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route($routeName));

        $this->get(route($routeName))->assertOk();
    }

    public function test_user_without_module_permission_is_redirected_to_public_home(): void
    {
        $user = User::factory()->create();

        Livewire::test('pages::public.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('login'))->assertRedirect(route('home'));
    }

    public function test_failed_login_does_not_authenticate_or_redirect(): void
    {
        $user = User::factory()->create();

        Livewire::test('pages::public.login')
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_guest_opening_protected_page_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
