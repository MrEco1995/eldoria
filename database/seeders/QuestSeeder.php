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
                'key' => 'q01_die_verschwundene_holzfaellerin',
                'title' => 'Die verschwundene Holzfaellerin',
                'location' => 'Ein kleines Dorf am Rand des Waldes der Sylvarin',
                'mood' => 'Unruhig, aber noch kein Krieg. Nur Angst.',
                'intro' => "Im Dorf Eichenfurt ist die junge Holzfaellerin Mara seit zwei Tagen verschwunden.\nSie ging wie gewohnt in den Wald.\nIhr Hund kam allein zurueck.\nIn der Naehe wurden seltsame Spuren gefunden.\nDer Dorfaelteste bittet die Gruppe um Hilfe.",
                'reward' => 'Etwas Gold, Unterkunft und Ruf in Eichenfurt.',
                'act_1' => "Die Gruppe findet:\n- Zerbrochene Aeste\n- Einen gefallenen Korb\n- Blut, aber nicht viel\n- Grosse, schwere Fussabdruecke\n\nDie Spuren fuehren tiefer in den Wald.\nOptional: Ein elfischer Beobachter verfolgt die Gruppe aus der Ferne.",
                'act_2' => "Die Gruppe entdeckt in einer alten Ruine einen verletzten jungen Ork, kaum aelter als 16.\nMara lebt, sie ist gefesselt, aber unverletzt.\nDer Ork erklaert: \"Ich wollte nicht toeten. Ich wollte reden.\"\n\nEr sagt:\n- Sein Stamm hungert.\n- Menschen haben Jagdgruende erweitert.\n- Er wollte Geiseln nehmen, um Verhandlungen zu erzwingen.\n- Er wurde selbst von etwas anderem angegriffen.\n- Etwas anderes ist im Wald.",
                'act_3' => "Waehrend des Gespraechs greift ein verwilderter Wolf an, seltsam aggressiv, mit schwarzen Adern unter dem Fell.\nNach dem Kampf zeigt sich: Das Tier ist krank.\nDie Verderbnis kommt aus einem kleinen, dunklen Teich in der Naehe.\nUrsache ist ein instabiles Ueberbleibsel aus den Schattenjahren, schwach, aber gefaehrlich.",
                'decision_point' => 'Die Gruppe muss entscheiden, was sie mit dem Ork tut.',
                'ending_release' => "Ende 1: Der Ork wird freigelassen.\nDie Gruppe ueberzeugt Mara, dass er sie nicht toeten wollte.\nSie helfen ihm zurueck Richtung Stamm.\nKonsequenz: Das Dorf bleibt misstrauisch, der Orkstamm bemerkt die Geste.",
                'ending_capture' => "Ende 2: Der Ork wird gefangen oder getoetet.\nMara ist gerettet und das Dorf zufrieden.\nAber ein Ork-Spaeher hat alles beobachtet.\nDer Stamm glaubt an Verrat.",
                'next_quest_release_title' => 'Gespraeche im Blutland',
                'next_quest_capture_title' => 'Das gebrochene Zeichen',
                'recommended_party_level' => 1,
                'difficulty' => 2,
                'is_active' => true,
                'sort_order' => 1,
            ],
        ];

        $keys = array_column($quests, 'key');
        Quest::query()->whereNotIn('key', $keys)->delete();

        foreach ($quests as $quest) {
            Quest::updateOrCreate(
                ['key' => $quest['key']],
                $quest
            );
        }
    }
}
