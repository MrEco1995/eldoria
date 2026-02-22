<?php

namespace Database\Seeders;

use App\Models\CharacterClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CharacterClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            ['name' => 'Magier', 'description' => 'Arkaner Zauberwirker mit Fokus auf Magie und Wissen.'],
            ['name' => 'Krieger', 'description' => 'Frontkaempfer mit hoher Belastbarkeit und Waffenpraesenz.'],
            ['name' => 'Waldlaeufer', 'description' => 'Spurenleser und Schuetze mit Naturbezug.'],
            ['name' => 'Assassine', 'description' => 'Heimlicher Kaempfer fuer praezise und schnelle Angriffe.'],
            ['name' => 'Priester', 'description' => 'Glaube und Heilung, oft mit unterstuetzender Magie.'],
            ['name' => 'Barde', 'description' => 'Unterstuetzer mit Charisma, Wissen und situativer Magie.'],
        ];

        foreach ($classes as $index => $class) {
            CharacterClass::updateOrCreate(
                ['name' => $class['name']],
                [
                    'slug' => Str::slug($class['name']),
                    'description' => $class['description'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
