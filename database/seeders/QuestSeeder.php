<?php

namespace Database\Seeders;

use App\Models\Quest;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    public function run(): void
    {
        $quests = [
            [
                'key' => 'q01_kraeuter_fuer_das_lager',
                'title' => 'Kräuter für das Lager',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Außenhaine von Eldoria',
                'summary' => 'Die Vorratskammer der Siedlung ist leer und die Heiler brauchen frische Mondblatt-Kräuter.',
                'objective' => 'Sammle 12 Mondblatt-Kräuter aus dem Hain und bringe sie zur Lagerheilerin zurück.',
                'recommended_party_level' => 1,
                'difficulty' => 1,
            ],
            [
                'key' => 'q02_splitter_des_aethersteins',
                'title' => 'Splitter des Äthersteins',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Steinbruch von Kharun',
                'summary' => 'Ein Schmied braucht reine Ätherstein-Splitter für Schutzrunen an den Stadttoren.',
                'objective' => 'Sammle 8 Ätherstein-Splitter zwischen den aktiven Bruchlinien und liefere sie bei der Schmiede ab.',
                'recommended_party_level' => 1,
                'difficulty' => 1,
            ],
            [
                'key' => 'q03_wasser_aus_der_quellgrotte',
                'title' => 'Wasser aus der Quellgrotte',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Westlicher Waldrand',
                'summary' => 'Nach einer Magiewelle ist das Dorfbrunnenwasser verdorben, nur die Quellgrotte ist noch rein.',
                'objective' => 'Sammle 5 versiegelte Wasseramphoren aus der Quellgrotte und bringe sie zum Dorfbrunnen.',
                'recommended_party_level' => 1,
                'difficulty' => 1,
            ],
            [
                'key' => 'q04_geleit_zur_flussfeste',
                'title' => 'Geleit zur Flussfeste',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Handelsweg am Silberfluss',
                'summary' => 'Eine junge Kartografin muss dringend zur Flussfeste, doch die Straße gilt als unsicher.',
                'objective' => 'Begleite die Kartografin zur Flussfeste und halte den Weg bis zum Osttor frei.',
                'recommended_party_level' => 1,
                'difficulty' => 2,
            ],
            [
                'key' => 'q05_geleit_zum_heilerhain',
                'title' => 'Geleit zum Heilerhain',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Nebelpfad der Sylvarin',
                'summary' => 'Ein verletzter Bote der Elfen muss den Heilerhain erreichen, bevor seine Wunden aufbrechen.',
                'objective' => 'Begleite den Boten lebend zum Heilerhain und schütze ihn bis zum Ritualkreis.',
                'recommended_party_level' => 1,
                'difficulty' => 2,
            ],
            [
                'key' => 'q06_verlorener_ring_des_wachtschmieds',
                'title' => 'Verlorener Ring des Wachtschmieds',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Unterstadt von Eldoria',
                'summary' => 'Der Meisterschmied hat seinen Runenring verloren, ohne den er die Stadttore nicht neu versiegeln kann.',
                'objective' => 'Finde den verlorenen Runenring in der Unterstadt und bringe ihn unbeschädigt zur Schmiede.',
                'recommended_party_level' => 1,
                'difficulty' => 2,
            ],
            [
                'key' => 'q07_die_verschwundene_karte',
                'title' => 'Die verschwundene Karte',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Alte Grenzwarte',
                'summary' => 'Eine Aufklärungskarte mit sicheren Routen in die Verlorenen Lande ist verschwunden.',
                'objective' => 'Finde die vermisste Grenzkarte in der alten Warte und übergib sie dem Hauptmann.',
                'recommended_party_level' => 1,
                'difficulty' => 2,
            ],
            [
                'key' => 'q08_nebel_an_der_ostpalisade',
                'title' => 'Nebel an der Ostpalisade',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Ostpalisade von Eldoria',
                'summary' => 'Der Nebel frisst nachts die Sichtlinien der Wachen. Mehrere Signalfackeln gingen bereits verloren.',
                'objective' => 'Sichere die Ostpalisade, erneuere die Signalfackeln und halte drei Nebelangriffe bis Sonnenaufgang stand.',
                'recommended_party_level' => 2,
                'difficulty' => 2,
            ],
            [
                'key' => 'q09_risse_im_altentor',
                'title' => 'Risse im Altentor',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Ruinenbezirk von Altentor',
                'summary' => 'Das alte Stadttor zeigt magische Risse, durch die Nachtwesen in die Vorstadt gelangen.',
                'objective' => 'Beschaffe drei Runenanker, versiegel die Risse am Altentor und verteidige die Runen bis zur Stabilisierung.',
                'recommended_party_level' => 2,
                'difficulty' => 3,
            ],
            [
                'key' => 'q10_stille_ueber_dem_hafen',
                'title' => 'Stille über dem Hafen',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Nordhafen',
                'summary' => 'Lieferungen bleiben aus, seit Schatten auf dem Wasser gesichtet wurden und Schiffe die Einfahrt meiden.',
                'objective' => 'Begleite die Hafenwache, vertreibe die Schatten aus dem Hafenbecken und stelle den Handel wieder her.',
                'recommended_party_level' => 2,
                'difficulty' => 3,
            ],
            [
                'key' => 'q11_die_letzte_faehrte',
                'title' => 'Die letzte Fährte',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Nordforst',
                'summary' => 'Eine Patrouille verschwand nahe eines alten Brunnenschachts, in dem der Ätherstrom flackert.',
                'objective' => 'Verfolge die letzte Fährte, rette die Patrouille aus dem Schacht und schließe den defekten Ätherknoten.',
                'recommended_party_level' => 2,
                'difficulty' => 3,
            ],
            [
                'key' => 'q12_siegelbruch_unter_eldoria',
                'title' => 'Siegelbruch unter Eldoria',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Unterstadtgewolbe',
                'summary' => 'Tief unter Eldoria bricht ein altes Siegel auf. Dunkle Wellen dringen durch stillgelegte Tunnel.',
                'objective' => 'Sichere den Zugang, aktiviere den Runenkreis und versiegle den Bruch, bevor die Welle die Oberstadt erreicht.',
                'recommended_party_level' => 3,
                'difficulty' => 4,
            ],
            [
                'key' => 'q13_der_schlaf_unter_dem_stein',
                'title' => 'Der Schlaf unter dem Stein',
                'chapter' => 'Zeitalter des brüchigen Friedens',
                'region' => 'Tiefe Bruchkammer',
                'summary' => 'Unter den Verlorenen Landen regt sich etwas Altes. Die Monolithen antworten mit Erschütterungen.',
                'objective' => 'Führe eine Einsatzgruppe zur Bruchkammer, halte den Kernraum gegen Schattenwesen und stabilisiere den Ätheranker.',
                'recommended_party_level' => 3,
                'difficulty' => 4,
            ],
        ];

        $keys = array_column($quests, 'key');
        Quest::query()->whereNotIn('key', $keys)->delete();

        foreach ($quests as $index => $quest) {
            Quest::updateOrCreate(
                ['key' => $quest['key']],
                [
                    ...$quest,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
