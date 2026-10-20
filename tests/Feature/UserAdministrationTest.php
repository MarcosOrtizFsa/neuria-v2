<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_can_open_user_administration(): void
    {
        $root = $this->root();
        $this->actingAs($root)->get('/admin/usuarios')->assertOk()->assertSee('Usuarios');
    }

    public function test_operator_without_permission_cannot_open_user_administration(): void
    {
        $operator = User::factory()->for(Role::factory()->create(['code' => 'OPERADOR']))->create();
        $this->actingAs($operator)->get('/admin/usuarios')->assertForbidden();
    }

    public function test_individual_denial_overrides_role_permission(): void
    {
        $this->seed(ModuleSeeder::class);
        $permission = Permission::where('code', 'USUARIOS.VER')->firstOrFail();
        $role = Role::factory()->create(['code' => 'ADMINISTRADOR']);
        $role->permissions()->attach($permission);
        $user = User::factory()->for($role)->create();
        $user->permissions()->attach($permission, ['allowed' => false]);
        $this->assertFalse($user->hasPermission('USUARIOS.VER'));
        $this->actingAs($user)->get('/admin/usuarios')->assertForbidden();
    }

    public function test_last_active_root_cannot_be_deactivated(): void
    {
        $root = $this->root();
        $this->actingAs($root)->put(route('admin.usuarios.update', $root), ['name' => $root->name, 'last_name' => $root->last_name, 'email' => $root->email, 'role_id' => $root->role_id, 'organization_id' => '', 'is_active' => 0])->assertSessionHasErrors('is_active');
        $this->assertTrue($root->fresh()->is_active);
    }

    private function root(): User
    {
        return User::factory()->for(Role::factory()->create(['code' => 'ROOT']))->create();
    }
}
