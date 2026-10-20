<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'description', 'is_system'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const HIERARCHY = ['CLIENTE' => 10, 'OPERADOR' => 20, 'ADMINISTRADOR' => 30, 'GERENTE' => 40, 'ROOT' => 50];

    public function level(): int
    {
        return self::HIERARCHY[$this->code] ?? 0;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
