<?php

namespace Database\Seeders;

use App\Models\Race;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RaceSeeder extends Seeder
{
    public function run(): void
    {
        $races = [
            [
                'name' => 'Menschen',
                'description' => 'Anpassungsfähig, ehrgeizig und kulturell extrem vielfältig.',
                'essence' => [
                    'Anpassungsfähig, ehrgeizig, emotional',
                    'Denken kurzfristiger als langlebige Völker',
                    'Stark in Politik, Handel und Organisation',
                    'Extrem vielfältig in Kultur und Moral',
                ],
                'appearance' => [
                    'Große genetische Vielfalt',
                    'Hautfarben von hell bis dunkel',
                    'Haarfarben breit gefächert',
                    'Kleidung stark statusabhängig (Bauer ↔ Adel)',
                ],
                'age_text' => 'Durchschnitt 60–80 Jahre, selten über 90 ohne Magie',
                'height_text' => '1,55 m – 1,95 m (Ø ca. 1,70–1,80 m)',
                'weight_text' => 'Stark variierend, Ø 55–110 kg',
                'good_with' => ['Zwerge (Handel)', 'Orks (Söldner)', 'Faelun (lokal)'],
                'bad_with' => ['Noctyr (Angst vor Manipulation)', 'Tharokh (Furcht vor Unbeugsamkeit)'],
            ],
            [
                'name' => 'Elfen (Sylvarin)',
                'description' => 'Ruhig, naturverbunden und auf Harmonie ausgerichtet.',
                'essence' => [
                    'Ruhig, kontrolliert, oft distanziert',
                    'Denken in Jahrhunderten',
                    'Naturverbunden, harmonieorientiert',
                    'Stolz, aber selten laut',
                ],
                'appearance' => [
                    'Schlank, anmutig',
                    'Spitze Ohren',
                    'Augen oft ungewöhnlich: silbern, smaragdgrün, bernstein',
                    'Haut meist hell oder leicht schimmernd',
                    'Kaum sichtbare Alterung',
                ],
                'age_text' => '300–600 Jahre, Reife mit ca. 50 Jahren',
                'height_text' => '1,70 m – 1,95 m (sehr schlanke Statur)',
                'weight_text' => '50–80 kg, oft leichter als Menschen gleicher Größe',
                'good_with' => ['Faelun', 'ausgewählte Menschen'],
                'bad_with' => ['Orks (alte Kriegswunden)', 'Noctyr (ungeklärte Schuld)'],
            ],
            [
                'name' => 'Zwerge (Kharun)',
                'description' => 'Diszipliniert, traditionsbewusst und kompromisslos verlässlich.',
                'essence' => [
                    'Diszipliniert, traditionsbewusst',
                    'Loyal, wenn Vertrauen verdient wurde',
                    'Direkt, pragmatisch',
                    'Geduldig, aber nachtragend',
                ],
                'appearance' => [
                    'Kräftig gebaut, breite Schultern',
                    'Dichte Bärte (auch bei vielen Zwergenfrauen)',
                    'Haut oft wettergegerbt',
                    'Augen dunkel oder steingrau',
                ],
                'age_text' => '180–300 Jahre, Reife mit etwa 40',
                'height_text' => '1,20 m – 1,50 m (kompakt und massiv)',
                'weight_text' => '70–120 kg, sehr hohe Knochendichte',
                'good_with' => ['Menschen', 'Tharokh'],
                'bad_with' => ['Orks (Blutfehden)', 'Noctyr (Geheimniskrämerei)'],
            ],
            [
                'name' => 'Orks (Grum)',
                'description' => 'Stammesorientiert, ehrengebunden und kompromisslos direkt.',
                'essence' => [
                    'Ehrenkodex, stammesorientiert',
                    'Emotional, direkt',
                    'Starker Familiensinn',
                    'Respektieren Stärke, verachten Feigheit',
                ],
                'appearance' => [
                    'Kräftig bis massiv',
                    'Haut: oliv, grau, dunkelgrün',
                    'Ausgeprägte Eckzähne',
                    'Narben gelten als Schmuck',
                    'Breite Kiefer, starke Hände',
                ],
                'age_text' => '50–70 Jahre, Reife mit ca. 15–18',
                'height_text' => '1,80 m – 2,20 m',
                'weight_text' => '90–160 kg, hoher Muskelanteil',
                'good_with' => ['Menschen (Söldner)', 'Tharokh'],
                'bad_with' => ['Elfen (jahrhundertelange Kriege)', 'Faelun (Jagdgebiete)'],
            ],
            [
                'name' => 'Faelun - Wandelblütige',
                'description' => 'Freiheitsliebende Sippenwesen zwischen Mensch und Tier.',
                'essence' => [
                    'Freiheitsliebend, naturverbunden',
                    'Denken zyklisch statt linear',
                    'Stark emotional an Natur und Sippe gebunden',
                    'Unruhig in Städten',
                ],
                'appearance' => [
                    'Humanoid mit tierischen Merkmalen',
                    'Hörner (Hirsch, Widder)',
                    'Hufe oder fellbedeckte Beine',
                    'Katzen- oder Rehaugen',
                    'Oft schlank und beweglich',
                ],
                'age_text' => '70–100 Jahre',
                'height_text' => '1,60 m – 1,90 m (sehr agil gebaut)',
                'weight_text' => '50–85 kg, leicht und drahtig',
                'good_with' => ['Elfen', 'Noctyr (seltene Bündnisse)'],
                'bad_with' => ['Menschen (Aberglaube)', 'Orks (Territoriale Konflikte)'],
            ],
            [
                'name' => 'Noctyr - Schattengeborene',
                'description' => 'Wissensbewahrer im Zwielicht zwischen Erinnerung und Geheimnis.',
                'essence' => [
                    'Beobachtend, analytisch',
                    'Sprechen wenig, denken viel',
                    'Bewahren Wissen und Erinnerungen',
                    'Emotionen kontrolliert, nicht kalt',
                ],
                'appearance' => [
                    'Blasse bis dunkle, matte Haut',
                    'Leuchtende oder ungewöhnlich helle Augen',
                    'Schlanke, lange Gliedmaßen',
                    'Manche mit subtilen federartigen Details',
                    'Bewegen sich lautlos',
                ],
                'age_text' => '90–140 Jahre, sichtbar aber langsam alternd',
                'height_text' => '1,70 m – 2,00 m (sehr schlank)',
                'weight_text' => '55–85 kg, wirken oft leichter als sie sind',
                'good_with' => ['Faelun', 'pragmatische Menschen'],
                'bad_with' => ['Elfen (alte Schuld)', 'Zwerge (Misstrauen)', 'Tharokh (Schatten vs. Beständigkeit)'],
            ],
            [
                'name' => 'Tharokh - Steinblütige',
                'description' => 'Massive, unbeugsame Langzeitdenker mit fast steinerner Präsenz.',
                'essence' => [
                    'Still, unbeugsam, prinzipientreu',
                    'Extrem geduldig',
                    'Kaum impulsiv',
                    'Denken langfristig, oft schwer greifbar für andere',
                ],
                'appearance' => [
                    'Große, massive Gestalten',
                    'Haut wirkt wie Stein oder Basalt',
                    'Rissartige Linien über den Körper',
                    'Augen glühen schwach (weiß, bernstein, rot)',
                    'Bewegungen langsam, aber kraftvoll',
                ],
                'age_text' => '150–250 Jahre, altern sehr langsam',
                'height_text' => '2,10 m – 2,60 m',
                'weight_text' => '180–350 kg, extrem dichtes Gewebe',
                'good_with' => ['Zwerge', 'Orks (respektvolle Stärke)'],
                'bad_with' => ['Noctyr (Manipulation)', 'Menschen (Expansion)'],
            ],
        ];

        foreach ($races as $index => $race) {
            Race::updateOrCreate(
                ['name' => $race['name']],
                [
                    ...$race,
                    'slug' => Str::slug($race['name']),
                    'hp_base' => $this->resolveHpBase($race['name']),
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function resolveHpBase(string $raceName): int
    {
        $normalized = (string) Str::of($raceName)->ascii()->lower();

        return match (true) {
            str_contains($normalized, 'mensch') => 14,
            str_contains($normalized, 'elf') => 11,
            str_contains($normalized, 'zwerg') => 18,
            str_contains($normalized, 'ork') => 20,
            str_contains($normalized, 'faelun') => 13,
            str_contains($normalized, 'noctyr') => 12,
            str_contains($normalized, 'tharokh') => 22,
            default => 12,
        };
    }
}
