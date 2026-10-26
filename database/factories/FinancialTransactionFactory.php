<?php

namespace Database\Factories;

use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialTransaction>
 */
class FinancialTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'expense',
            'occurred_on' => now()->toDateString(),
            'description' => 'Biaya operasional',
            'operating_expense' => 50000,
            'cash_out' => 50000,
        ];
    }
}
