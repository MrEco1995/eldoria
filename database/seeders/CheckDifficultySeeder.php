<?php

namespace Database\Seeders;

use App\Models\CheckDifficulty;
use Illuminate\Database\Seeder;

class CheckDifficultySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['key' => 'leicht', 'label' => 'Leicht', 'sg' => 8, 'sort_order' => 10],
            ['key' => 'normal', 'label' => 'Normal', 'sg' => 12, 'sort_order' => 20],
            ['key' => 'schwer', 'label' => 'Schwer', 'sg' => 15, 'sort_order' => 30],
            ['key' => 'sehr_schwer', 'label' => 'Sehr schwer', 'sg' => 18, 'sort_order' => 40],
            ['key' => 'legendaer', 'label' => 'Legendaer', 'sg' => 22, 'sort_order' => 50],
        ];

        foreach ($rows as $row) {
            CheckDifficulty::query()->updateOrCreate(
                ['key' => $row['key']],
                [
                    'label' => $row['label'],
                    'sg' => $row['sg'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
