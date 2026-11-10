<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactLinkFactory extends Factory
{
    public function definition(): array
    {
        return ['group' => 'admin', 'title' => 'Admin Free Fire', 'subtitle' => 'Khusus jual beli akun FF', 'value' => '6281111111111', 'badge' => null, 'sort_order' => 0];
    }
}
