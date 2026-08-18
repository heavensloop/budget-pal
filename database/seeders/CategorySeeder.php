<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the system-default categories, shared by every user.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Food', 'type' => CategoryType::Need],
            ['name' => 'Transport', 'type' => CategoryType::Need],
            ['name' => 'Housing', 'type' => CategoryType::Need],
            ['name' => 'Utilities', 'type' => CategoryType::Need],
            ['name' => 'Health', 'type' => CategoryType::Need],
            ['name' => 'Family', 'type' => CategoryType::Both],
            ['name' => 'Entertainment', 'type' => CategoryType::Want],
            ['name' => 'Debt', 'type' => CategoryType::Want],
            ['name' => 'Savings', 'type' => CategoryType::Both],
            ['name' => 'Giving', 'type' => CategoryType::Both],
            ['name' => 'Other', 'type' => CategoryType::Both],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate([
                'user_id' => null,
                'name' => $category['name'],
            ], [
                'type' => $category['type'],
            ]);
        }
    }
}
