<?php

namespace Database\Seeders;

use App\Models\StarterWeapon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StarterWeaponSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            'Menschen' => [
                'Magier' => 'Holzstab',
                'Krieger' => 'Kurzschwert',
                'Waldläufer' => 'Kurzbogen',
                'Assassine' => 'Dolch',
                'Priester' => 'Keule',
                'Barde' => 'Dolch',
            ],
            'Elfen (Sylvarin)' => [
                'Magier' => 'Holzstab',
                'Krieger' => 'Kurzschwert',
                'Waldläufer' => 'Kurzbogen',
                'Assassine' => 'Dolch',
                'Priester' => 'Keule',
                'Barde' => 'Dolch',
            ],
            'Zwerge (Kharun)' => [
                'Magier' => 'Holzstab',
                'Krieger' => 'Handaxt',
                'Waldläufer' => 'Kurzbogen',
                'Assassine' => 'Dolch',
                'Priester' => 'Keule',
                'Barde' => 'Dolch',
            ],
            'Orks (Grum)' => [
                'Magier' => 'Holzstab',
                'Krieger' => 'Handaxt',
                'Waldläufer' => 'Speer',
                'Assassine' => 'Dolch',
                'Priester' => 'Keule',
                'Barde' => 'Dolch',
            ],
            'Faelun - Wandelblütige' => [
                'Magier' => 'Holzstab',
                'Krieger' => 'Speer',
                'Waldläufer' => 'Kurzbogen',
                'Assassine' => 'Dolch',
                'Priester' => 'Keule',
                'Barde' => 'Dolch',
            ],
            'Noctyr - Schattengeborene' => [
                'Magier' => 'Holzstab',
                'Krieger' => 'Kurzschwert',
                'Waldläufer' => 'Kurzbogen',
                'Assassine' => 'Dolch',
                'Priester' => 'Keule',
                'Barde' => 'Dolch',
            ],
            'Tharokh - Steinblütige' => [
                'Magier' => 'Holzstab',
                'Krieger' => 'Keule',
                'Waldläufer' => 'Speer',
                'Assassine' => 'Dolch',
                'Priester' => 'Keule',
                'Barde' => 'Dolch',
            ],
        ];

        $seen = [];

        foreach ($matrix as $raceName => $classes) {
            foreach ($classes as $className => $weaponName) {
                $raceKey = $this->normalizeLookupKey($raceName);
                $classKey = $this->normalizeLookupKey($className);
                $seen[] = "{$raceKey}|{$classKey}";

                StarterWeapon::updateOrCreate(
                    [
                        'race_key' => $raceKey,
                        'class_key' => $classKey,
                    ],
                    [
                        'race_name' => $raceName,
                        'class_name' => $className,
                        'weapon_name' => $weaponName,
                        'is_active' => true,
                    ]
                );
            }
        }

        StarterWeapon::query()->get()->each(function (StarterWeapon $entry) use ($seen) {
            $key = "{$entry->race_key}|{$entry->class_key}";
            if (!in_array($key, $seen, true)) {
                $entry->delete();
            }
        });
    }

    private function normalizeLookupKey(string $value): string
    {
        return (string) Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/', ' ')
            ->trim();
    }
}

