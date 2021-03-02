<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Status::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $date_time = $this->faker->date . ' ' . $this->faker->time;
        return [
            'user_id' => $this->faker->randomElement(['1', '2', '3']),
            'content' => $this->faker->text(),
            'created_at' => $date_time,
            'updated_at' => $date_time,
        ];
    }
}
