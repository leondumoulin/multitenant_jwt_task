<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        $slug = Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'db_name' => 'tenant_' . $slug,
            'db_user' => $this->faker->optional(0.7)->passthrough('user_' . $slug),
            'db_pass' => $this->faker->optional(0.7)->passthrough(Str::random(16)),
            'status' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the tenant is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => true,
        ]);
    }

    /**
     * Indicate that the tenant is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => false,
        ]);
    }

    /**
     * Indicate that the tenant has database credentials.
     */
    public function withDbCredentials(): static
    {
        return $this->state(function (array $attributes) {
            $slug = $attributes['slug'] ?? Str::slug($attributes['name']);
            return [
                'db_user' => 'user_' . $slug,
                'db_pass' => Str::random(16),
            ];
        });
    }

    /**
     * Indicate that the tenant has no database credentials.
     */
    public function withoutDbCredentials(): static
    {
        return $this->state(fn(array $attributes) => [
            'db_user' => null,
            'db_pass' => null,
        ]);
    }
}
