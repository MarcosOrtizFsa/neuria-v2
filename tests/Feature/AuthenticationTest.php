<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_portal_loads_without_authentication(): void
    {
        $this->get('/')->assertOk()->assertSee('NEURIA');
    }

    public function test_private_panel_requires_authentication(): void
    {
        $this->get('/panel')->assertRedirect('/login');
    }

    public function test_valid_credentials_authenticate_an_active_user(): void
    {
        $role = Role::factory()->create(['code' => 'OPERADOR']);
        $user = User::factory()->for($role)->create(['password' => Hash::make('Clave-Segura-123')]);
        $this->post('/login', ['email' => $user->email, 'password' => 'Clave-Segura-123'])->assertRedirect('/panel');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        $user = User::factory()->inactive()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_invalidates_authentication(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
