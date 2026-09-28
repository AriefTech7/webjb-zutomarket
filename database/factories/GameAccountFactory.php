<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameAccountFactory extends Factory
{
    public function definition(): array
    {
        return ['category_id' => Category::factory(), 'title' => fake()->sentence(3), 'slug' => fake()->unique()->slug(), 'price' => 350000, 'description' => 'Akun pilihan', 'specifications' => 'Rank: Mythic; Skin: 150; Hero: Fanny; Level: 60; Item: Sword; Server: Asia', 'main_image' => 'demo/arena.svg', 'status' => 'available'];
    }
}
