<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Chronik von Eldoria" />

    <div class="min-vh-100 position-relative overflow-hidden" style="background: linear-gradient(180deg, #0c1426 0%, #131b2e 45%, #1d2537 100%);">
        <div
            class="position-absolute top-0 start-0 w-100 h-100"
            style="background: radial-gradient(70% 50% at 20% 0%, rgba(77, 208, 225, 0.16), transparent 60%), radial-gradient(60% 45% at 80% 10%, rgba(245, 166, 35, 0.12), transparent 70%);"
        ></div>

        <div class="container position-relative py-5" style="z-index: 1; max-width: 980px;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <h1 class="display-6 text-white fw-bold mb-0">Chronik von Eldoria</h1>
                <div class="d-flex gap-2">
                    <Link :href="route('welcome')" class="btn btn-outline-light btn-sm">
                        Zur Startseite
                    </Link>
                    <Link v-if="$page.props.auth.user" :href="route('lobby')" class="btn btn-light btn-sm">
                        Zur Lobby
                    </Link>
                    <Link v-else :href="route('login')" class="btn btn-light btn-sm">
                        Anmelden
                    </Link>
                </div>
            </div>

            <div class="text-light-emphasis mb-4">
                Die Geschichte Eldorias vom ersten Erwachen bis zum brüchigen Frieden.
            </div>

            <div class="card border-0 shadow-lg" style="background: rgba(255, 255, 255, 0.95); border-radius: 1rem;">
                <div class="card-body p-4 p-md-5">
                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">I. Das Zeitalter des Erwachens</h2>
                        <p>Bevor Eldoria einen Namen hatte, war es eine Welt aus roher Kraft. Der Himmel war heller, die Nächte dunkler, und der Ätherstrom floss frei wie ein ungezähmter Fluss.</p>
                        <p>Es gab keine Reiche. Keine Kronen. Keine Banner. Nur Stille.</p>
                        <p>Dann bebten die Berge. Nicht durch Erdbeben. Nicht durch Krieg. Sondern durch Erwachen.</p>
                        <p>Aus steinernen Flanken lösten sich Gestalten mit rissiger Haut und glühenden Augen: die Tharokh. Sie waren nicht geboren worden, sie hatten geschlafen. Sie sprachen nicht, kannten keine Götter, doch sie verstanden die Welt tiefer als jedes andere Volk.</p>
                        <p>Sie spürten Spannungen im Gestein und wo der Ätherstrom zu stark drückte. Ohne Zauber, ohne Worte, stellten sie sich dorthin, als würden sie die Welt selbst stützen.</p>
                        <p>Jahrhunderte später nahm Nebel in den Wäldern Gestalt an. Zwischen Wurzeln und uralten Eichen entstanden Wesen mit Hörnern, Hufen und wachen Augen: die Faelun. Sie waren Bewegung, Wandel und Freiheit.</p>
                        <p>Die Faelun hörten den Atem der Welt. Als der Ätherstrom anschwoll, traten aus diesem Fluss die ersten Elfen, die Sylvarin. Sie lenkten Magie bewusst, sangen Lieder, die den Wind formten, und verstanden früh: Macht kann erschaffen und zerstören.</p>
                        <p>Diese drei Völker lebten lange im Gleichgewicht. Nicht als Bündnis. Nicht im Krieg. Sondern in einer stillen Balance.</p>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">II. Das Zeitalter der Flammen und Kronen</h2>
                        <p>Die See brachte Veränderung. An Eldorias Küsten zerschellten primitive Schiffe, und aus ihnen krochen Erschöpfte: die Menschen.</p>
                        <p>Sie waren schwach, sterblich und kurzlebig. Doch sie besaßen etwas, das die älteren Völker unterschätzten: Unruhe.</p>
                        <p>Sie bauten Hütten, dann Dörfer, dann Mauern. Sie stellten Fragen über Magie, Alter und Herkunft der anderen Völker. Die Elfen beobachteten sie mit Skepsis, die Faelun hielten Abstand, die Tharokh blieben unbewegt.</p>
                        <p>Doch die Menschen lernten schnell und erforschten Magie nicht aus Harmonie, sondern aus Ehrgeiz.</p>
                        <p>Zur selben Zeit öffneten sich die Berge: Die Zwerge von Kharun traten ans Licht. Sie waren nicht Teil des Steins wie die Tharokh, sie beherrschten ihn. Handel entstand. Wachstum folgte. Und mit jeder Krone wuchs der Wunsch nach mehr.</p>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">III. Die Blutfehden</h2>
                        <p>Aus den Ebenen im Süden kamen die Grum, die Orks. Kein plötzlicher Sturm, sondern ein Volk, das immer da war und nun zahlreicher wurde.</p>
                        <p>Ihre Stämme suchten Land. Missverständnisse an Grenzfestungen entfachten Vergeltung: Angriffe, verbrannte Ernten, erschlagene Boten.</p>
                        <p>Menschen riefen Ritter, Zwerge schlossen Pässe, Elfen schossen aus den Schatten. Die Orks antworteten mit Krieg.</p>
                        <p>Jahrzehnte brannten die Grenzlande. Wälder wurden gerodet, Bergpässe mit Leichen gefüllt, Generationen mit Hass erzogen.</p>
                        <p>Am Ende gab es keinen Sieg, nur Erschöpfung. Ein brüchiger Frieden entstand, doch Misstrauen blieb.</p>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">IV. Die Schattenjahre</h2>
                        <p>Magie war kein Geheimnis mehr. Menschen gründeten Akademien, Zwerge schmiedeten Runen, Elfen warnten. Im Osten sammelten die Noctyr Wissen, das andere vergessen wollten.</p>
                        <p>Sie entdeckten eine gefährliche Wahrheit: Der Ätherstrom war nicht von Natur aus ruhig. Er war gebunden.</p>
                        <p>Tief unter den Verlorenen Landen lag etwas Versiegeltes. Ein Ritual sollte die Siegel stärken, doch niemand wusste mehr genau, was es wirklich bewirken würde.</p>
                        <p>In jener Nacht riss der Himmel auf. Magie brach unkontrolliert hervor. Licht wurde schwarz. Schatten bekamen Form. Eine ganze Region wurde verdorben.</p>
                        <p>Wälder starben, Tiere mutierten, Städte verschwanden. Die Noctyr wurden beschuldigt, Elfen zogen sich zurück, Menschen schwiegen über ihre Rolle. Seitdem flackert Magie in den Verlorenen Landen wie eine offene Wunde.</p>
                    </section>

                    <section class="mb-0">
                        <h2 class="h4 fw-bold mb-3">V. Das Zeitalter des brüchigen Friedens</h2>
                        <p>Heute wirkt Eldoria stabil: Das Menschenreich ist mächtig, die Orks organisiert, die Zwerge graben tiefer, die Elfen lauschen dem Wald, die Faelun spüren Unruhe, die Noctyr bewahren Erinnerungen, und die Tharokh stehen.</p>
                        <p>Doch Monolithen beginnen zu vibrieren. Magie flackert an Orten, an denen sie schweigen sollte. Und unter den Verlorenen Landen bewegt sich etwas, das nie ganz versiegelt war.</p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>

