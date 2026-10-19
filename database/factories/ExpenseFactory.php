<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_category_id' => ExpenseCategory::factory(),
            'expense_date' => '2026-10-01',
            'amount' => 50000,
            'description' => 'Biaya operasional',
            'created_by' => User::factory(),
            'submission_token' => fake()->uuid(),
        ];
    }
}
