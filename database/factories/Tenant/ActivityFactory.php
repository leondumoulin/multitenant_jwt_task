<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Activity;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['call', 'email', 'meeting', 'note', 'task']),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['completed', 'pending', 'cancelled']),
        ];
    }

    /**
     * Indicate that the activity is a call.
     */
    public function call(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'call',
        ]);
    }

    /**
     * Indicate that the activity is an email.
     */
    public function email(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'email',
        ]);
    }

    /**
     * Indicate that the activity is a meeting.
     */
    public function meeting(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'meeting',
        ]);
    }

    /**
     * Indicate that the activity is completed.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the activity is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
