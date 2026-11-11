<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ManualSaleFactory extends Factory
{
    public function definition(): array
    {
        return ['type' => 'joki', 'item' => fake()->sentence(3), 'amount' => 60000, 'sold_on' => '2026-10-15', 'note' => null];
    }
}
