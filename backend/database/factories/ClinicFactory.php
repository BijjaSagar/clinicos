<?php

namespace Database\Factories;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicFactory extends Factory
{
    protected $model = Clinic::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'plan' => 'small',
            'facility_type' => 'clinic',
            'is_active' => true,
            'settings' => ['setup_completed' => true],
        ];
    }
}
