<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::random(8);

        return [
            'name' => $name,
            'slug' => 'perm-'.Str::lower($name),
            'group' => 'Test',
            'description' => null,
        ];
    }
}
