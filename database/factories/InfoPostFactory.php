<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InfoPostFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => 'Cara beli akun', 'image_path' => 'info/cara-beli.png', 'link_url' => null, 'sort_order' => 0];
    }
}
