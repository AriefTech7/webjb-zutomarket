<?php

namespace Database\Factories;

use App\Models\GameAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentalPackageFactory extends Factory
{
    public function definition(): array
    {
        return ['game_account_id' => GameAccount::factory()->state(['is_rental' => true]), 'duration' => '6 Jam', 'price' => 25000, 'sort_order' => 0];
    }
}
