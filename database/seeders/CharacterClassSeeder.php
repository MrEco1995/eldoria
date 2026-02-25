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
                    'hp_base' => $this->resolveHpBase($class['name']),
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function resolveHpBase(string $className): int
    {
        $normalized = (string) Str::of($className)->ascii()->lower();

        return match (true) {
            str_contains($normalized, 'krieger') => 16,
            str_contains($normalized, 'waldlaeufer') || str_contains($normalized, 'waldlaufer') => 13,
            str_contains($normalized, 'assassine') => 12,
            str_contains($normalized, 'priester') => 11,
            str_contains($normalized, 'barde') => 10,
            str_contains($normalized, 'magier') => 8,
            default => 10,
        };
    }
}
