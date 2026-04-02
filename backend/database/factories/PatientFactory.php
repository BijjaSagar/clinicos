<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('98########'),
            'email' => fake()->safeEmail(),
            'sex' => fake()->randomElement(['M', 'F']),
            'dob' => fake()->date('Y-m-d', '-20 years'),
        ];
    }
}
