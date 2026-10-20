<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = ['INICIO' => 'Inicio', 'USUARIOS' => 'Usuarios', 'MEDIOS' => 'Medios', 'LINEA_EDITORIAL' => 'Línea editorial', 'AGENCIA_NOTICIAS' => 'Agencia de Noticias', 'CONTENIDOS' => 'Contenidos', 'API_CONSUMOS' => 'API y Consumos', 'PORTAL_PUBLICO' => 'Portal público', 'CONFIGURACION' => 'Configuración'];
        $operations = ['VER', 'ALTA', 'MODIFICAR', 'BAJA', 'APROBAR', 'EXPORTAR', 'CONFIGURAR'];
        foreach ($modules as $code => $name) {
            $module = Module::updateOrCreate(['code' => $code], ['name' => $name, 'sort_order' => array_search($code, array_keys($modules), true) + 1]);
            foreach ($operations as $operation) {
                Permission::updateOrCreate(['code' => "{$code}.{$operation}"], ['module_id' => $module->id, 'operation' => $operation, 'name' => "{$operation} {$name}"]);
            }
        }
    }
}
