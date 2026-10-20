<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['ROOT' => 'Administrador técnico global', 'GERENTE' => 'Gestión general', 'ADMINISTRADOR' => 'Administrador autorizado', 'OPERADOR' => 'Operador', 'CLIENTE' => 'Cliente'];
        foreach ($roles as $code => $description) {
            Role::updateOrCreate(['code' => $code], ['name' => $code, 'description' => $description, 'is_system' => true]);
        }
        $manager = Role::where('code', 'GERENTE')->firstOrFail();
        $manager->permissions()->sync(Permission::where('code', 'like', 'USUARIOS.%')->pluck('id'));
        $admin = Role::where('code', 'ADMINISTRADOR')->firstOrFail();
        $admin->permissions()->sync(Permission::whereIn('code', ['INICIO.VER', 'USUARIOS.VER', 'USUARIOS.ALTA', 'USUARIOS.MODIFICAR'])->pluck('id'));
        $operator = Role::where('code', 'OPERADOR')->firstOrFail();
        $operator->permissions()->sync(Permission::whereIn('code', ['INICIO.VER', 'USUARIOS.VER'])->pluck('id'));
        $client = Role::where('code', 'CLIENTE')->firstOrFail();
        $client->permissions()->sync(Permission::where('code', 'INICIO.VER')->pluck('id'));
    }
}
