<?php

namespace Database\Seeders;

use App\Models\Talent;
use Illuminate\Database\Seeder;

class TalentSeeder extends Seeder
{
    public function run(): void
    {
        $talents = [
            ['key' => 'kraft', 'label' => 'Kraft', 'category' => 'Körperliche Talente', 'description' => 'Rohe Muskelstärke und physische Dominanz.'],
            ['key' => 'ausdauer', 'label' => 'Ausdauer', 'category' => 'Körperliche Talente', 'description' => 'Belastbarkeit und Durchhaltevermögen.'],
            ['key' => 'geschicklichkeit', 'label' => 'Geschicklichkeit', 'category' => 'Körperliche Talente', 'description' => 'Feinmotorik und präzise Bewegungskontrolle.'],
            ['key' => 'beweglichkeit', 'label' => 'Beweglichkeit', 'category' => 'Körperliche Talente', 'description' => 'Schnelligkeit, Reaktion und Balance.'],

            ['key' => 'intelligenz', 'label' => 'Intelligenz', 'category' => 'Geistige Talente', 'description' => 'Analytisches Denken und Wissensspeicher.'],
            ['key' => 'weisheit', 'label' => 'Weisheit', 'category' => 'Geistige Talente', 'description' => 'Instinkt, Erfahrung und innere Erkenntnis.'],
            ['key' => 'willenskraft', 'label' => 'Willenskraft', 'category' => 'Geistige Talente', 'description' => 'Mentale Stärke und emotionale Kontrolle.'],
            ['key' => 'magische_affinitaet', 'label' => 'Magische Affinität', 'category' => 'Geistige Talente', 'description' => 'Verbindung zur arkanen Energie und Zauberkontrolle.'],

            ['key' => 'charisma', 'label' => 'Charisma', 'category' => 'Soziale Talente', 'description' => 'Ausstrahlung, Führung und Sympathie.'],
            ['key' => 'manipulation', 'label' => 'Manipulation', 'category' => 'Soziale Talente', 'description' => 'Täuschen, Lügen und subtile Einflussnahme.'],
            ['key' => 'einschuechterung', 'label' => 'Einschüchterung', 'category' => 'Soziale Talente', 'description' => 'Dominanz und furchteinflößende Präsenz.'],
            ['key' => 'empathie', 'label' => 'Empathie', 'category' => 'Soziale Talente', 'description' => 'Gefühle und Absichten anderer erkennen.'],

            ['key' => 'ueberleben', 'label' => 'Überleben', 'category' => 'Überlebens- & Welt-Talente', 'description' => 'In der Wildnis bestehen und Orientierung behalten.'],
            ['key' => 'handwerk', 'label' => 'Handwerk', 'category' => 'Überlebens- & Welt-Talente', 'description' => 'Herstellen, reparieren und verbessern von Gegenständen.'],
            ['key' => 'heilkunde', 'label' => 'Heilkunde', 'category' => 'Überlebens- & Welt-Talente', 'description' => 'Wunden versorgen und Krankheiten behandeln.'],
            ['key' => 'wahrnehmung', 'label' => 'Wahrnehmung', 'category' => 'Überlebens- & Welt-Talente', 'description' => 'Gefahren, Fallen und Details erkennen.'],

            ['key' => 'nahkampf', 'label' => 'Nahkampf', 'category' => 'Kampftalente', 'description' => 'Effektivität im direkten Waffenkampf.'],
            ['key' => 'fernkampf', 'label' => 'Fernkampf', 'category' => 'Kampftalente', 'description' => 'Präzision auf Distanz.'],
            ['key' => 'blocken', 'label' => 'Blocken', 'category' => 'Kampftalente', 'description' => 'Defensives Abfangen und Schaden reduzieren.'],
        ];

        foreach ($talents as $index => $talent) {
            Talent::updateOrCreate(
                ['key' => $talent['key']],
                [
                    ...$talent,
                    'max_points' => 15,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
