<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Deal;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Deal>
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'value' => $this->faker->randomFloat(2, 100, 100000),
            'status' => $this->faker->randomElement(['open', 'closed', 'won', 'lost']),
            'expected_close_date' => $this->faker->optional()->dateTimeBetween('now', '+3 months'),
        ];
    }

    /**
     * Indicate that the deal is open.
     */
    public function open(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'open',
        ]);
    }

    /**
     * Indicate that the deal is closed.
     */
    public function closed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'closed',
        ]);
    }

    /**
     * Indicate that the deal is won.
     */
    public function won(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'won',
        ]);
    }

    /**
     * Indicate that the deal is lost.
     */
    public function lost(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'lost',
        ]);
    }
}
