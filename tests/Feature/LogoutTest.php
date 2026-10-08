<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_ends_authentication_clears_session_and_redirects_to_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['demo_session_value' => 'private', '_token' => 'old-token']);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'))->assertSessionMissing('demo_session_value');
        $this->assertGuest();
        $this->assertNotSame('old-token', session()->token());
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_execute_authenticated_logout_action(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_get_request_does_not_log_user_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('logout'))->assertStatus(405);

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_layout_renders_both_logout_forms_with_confirmation(): void
    {
        $user = User::factory()->create();
        $user->givePermission(Permission::create(['name' => 'master-data.manage', 'display_name' => 'Master Data']));
        $this->actingAs($user);

        $response = $this->get(route('routes'));

        $response->assertOk()->assertSee('Yakin ingin keluar?');
        $this->assertSame(2, substr_count($response->getContent(), 'action="'.route('logout').'"'));
        $response->assertDontSee('wire:click="logout"', false);
    }
}
