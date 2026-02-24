<?php

namespace Database\Seeders;

use App\Models\PointOfInterest;
use Illuminate\Database\Seeder;

class PointOfInterestSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            [
                'slug' => 'capital-eldoria',
                'name' => 'Hauptstadt Eldoria',
                'type' => 'landmark',
                'x_percent' => 49.00,
                'y_percent' => 44.00,
                'min_zoom' => 1.00,
                'description' => 'Politisches Zentrum des Reiches und haeufigster Treffpunkt fuer neue Auftraege.',
                'sort_order' => 10,
            ],
            [
                'slug' => 'northwatch',
                'name' => 'Nordwacht',
                'type' => 'landmark',
                'x_percent' => 58.00,
                'y_percent' => 20.00,
                'min_zoom' => 1.00,
                'description' => 'Festung an der noerdlichen Grenze. Hohe Praesenz von Wachen und Patrouillen.',
                'sort_order' => 20,
            ],
            [
                'slug' => 'silverwald',
                'name' => 'Silberwald',
                'type' => 'landmark',
                'x_percent' => 36.00,
                'y_percent' => 51.00,
                'min_zoom' => 1.00,
                'description' => 'Dichter Wald mit alten Ruinen und seltenen Ressourcen.',
                'sort_order' => 30,
            ],
            [
                'slug' => 'ashen-coast',
                'name' => 'Aschenkueste',
                'type' => 'landmark',
                'x_percent' => 23.00,
                'y_percent' => 69.00,
                'min_zoom' => 1.00,
                'description' => 'Gefaehrliche Kuestenregion, bekannt fuer Piraten und verlorene Schaetze.',
                'sort_order' => 40,
            ],
            [
                'slug' => 'falkengrund',
                'name' => 'Falkengrund',
                'type' => 'village',
                'x_percent' => 53.00,
                'y_percent' => 48.00,
                'min_zoom' => 1.70,
                'description' => 'Kleines Dorf suedoestlich der Hauptstadt, bekannt fuer Pferde und Kurierdienste.',
                'sort_order' => 50,
            ],
            [
                'slug' => 'moorwinkel',
                'name' => 'Moorwinkel',
                'type' => 'village',
                'x_percent' => 41.00,
                'y_percent' => 58.00,
                'min_zoom' => 1.80,
                'description' => 'Abgelegenes Siedlungsgebiet am Rand eines Nebelmoors.',
                'sort_order' => 60,
            ],
            [
                'slug' => 'sonnbruch',
                'name' => 'Sonnbruch',
                'type' => 'village',
                'x_percent' => 29.00,
                'y_percent' => 63.00,
                'min_zoom' => 2.00,
                'description' => 'Fischerdorf mit kleinem Hafen und reger Kuestenfahrt.',
                'sort_order' => 70,
            ],
            [
                'slug' => 'steinkamm',
                'name' => 'Steinkamm',
                'type' => 'village',
                'x_percent' => 61.00,
                'y_percent' => 31.00,
                'min_zoom' => 2.10,
                'description' => 'Bergweiler mit Erzschaechten und rauem Klima.',
                'sort_order' => 80,
            ],
        ];

        foreach ($entries as $entry) {
            PointOfInterest::updateOrCreate(
                ['slug' => $entry['slug']],
                [
                    ...$entry,
                    'is_active' => true,
                ]
            );
        }
    }
}
