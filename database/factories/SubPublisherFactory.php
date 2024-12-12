<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SubPublisher;

class SubPublisherFactory extends Factory
{
    protected $model = SubPublisher::class;

    public function definition(): array
    {
        return [
            'publisher_id' => rand(2, 30),
            'display_name' => $this->faker->company . ' Database',
            'invoice_group' => $this->faker->word,
            'notes' => $this->faker->optional()->sentence,
            'is_active' => $this->faker->boolean(80),
            'is_primary' => $this->faker->boolean(20),
        ];
    }
}
