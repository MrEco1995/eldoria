<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Regelwerk von Eldoria" />

    <div class="min-vh-100 position-relative overflow-hidden" style="background: linear-gradient(180deg, #0c1426 0%, #131b2e 45%, #1d2537 100%);">
        <div
            class="position-absolute top-0 start-0 w-100 h-100"
            style="background: radial-gradient(70% 50% at 20% 0%, rgba(77, 208, 225, 0.16), transparent 60%), radial-gradient(60% 45% at 80% 10%, rgba(245, 166, 35, 0.12), transparent 70%);"
        ></div>

        <div class="container position-relative py-5" style="z-index: 1; max-width: 980px;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <h1 class="display-6 text-white fw-bold mb-0">Regelwerk von Eldoria</h1>
                <div class="d-flex gap-2">
                    <Link :href="route('lore.history')" class="btn btn-outline-light btn-sm">
                        Zur Chronik
                    </Link>
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
                Eldoria Würfelsystem in der Kurzfassung.
            </div>

            <div class="card border-0 shadow-lg" style="background: rgba(255, 255, 255, 0.95); border-radius: 1rem;">
                <div class="card-body p-4 p-md-5">
                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">1. Grundregel</h2>
                        <p>Für jede Aktion wird gewürfelt:</p>
                        <p><strong>W20 + Talent + Modifikatoren</strong></p>
                        <p>gegen</p>
                        <p><strong>Schwierigkeitsgrad (SG)</strong></p>
                        <p class="mb-0">Wenn das Ergebnis <strong>&ge; SG</strong> ist, gilt die Probe als Erfolg.</p>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">2. Talente</h2>
                        <p>Talente haben Werte von <strong>0 bis 15</strong> und werden zum Wurf addiert.</p>
                        <p class="mb-1">Beispiele:</p>
                        <ul class="mb-0">
                            <li>Kraft</li>
                            <li>Geschicklichkeit</li>
                            <li>Wahrnehmung</li>
                            <li>Nahkampf</li>
                            <li>Fernkampf</li>
                            <li>Blocken</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">3. Schwierigkeitsgrade (SG)</h2>
                        <p>Der Spielleiter entscheidet den SG.</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Schwierigkeit</th>
                                        <th>SG</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>leicht</td><td>8</td></tr>
                                    <tr><td>normal</td><td>12</td></tr>
                                    <tr><td>schwer</td><td>15</td></tr>
                                    <tr><td>sehr schwer</td><td>18</td></tr>
                                    <tr><td>legendär</td><td>22</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">4. Erfolgsgrade</h2>
                        <p>Nicht jeder Erfolg ist gleich gut.</p>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Differenz zum SG</th>
                                        <th>Ergebnis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>0 bis 4</td><td>normaler Erfolg</td></tr>
                                    <tr><td>5 bis 9</td><td>guter Erfolg</td></tr>
                                    <tr><td>10+</td><td>kritischer Erfolg</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="mb-1"><strong>Beispiel:</strong></p>
                        <p class="mb-1">SG = 12</p>
                        <p class="mb-1">Ergebnis = 19</p>
                        <p class="mb-0">Differenz = 7, also <strong>guter Erfolg</strong>.</p>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">5. Kritische Würfe</h2>
                        <ul class="mb-0">
                            <li><strong>Natürliche 20</strong>: automatischer Erfolg und kritischer Effekt.</li>
                            <li><strong>Natürliche 1</strong>: kritischer Fehlschlag.</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">6. Vorteil und Nachteil</h2>
                        <ul class="mb-0">
                            <li><strong>Vorteil</strong>: 2W20 würfeln, den höheren Wert nehmen.</li>
                            <li><strong>Nachteil</strong>: 2W20 würfeln, den niedrigeren Wert nehmen.</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">7. Kampfsystem</h2>
                        <p><strong>Angriff:</strong> W20 + Nahkampf/Fernkampf</p>
                        <p>gegen</p>
                        <p><strong>Verteidigung:</strong> 10 + Beweglichkeit + Blocken</p>
                        <p class="mb-0">Wenn Angriff <strong>&ge;</strong> Verteidigung ist, ist es ein Treffer.</p>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">8. Schaden</h2>
                        <p><strong>Schaden = Waffe + Kraftbonus - Rüstung</strong></p>
                        <p class="mb-1"><strong>Beispiel:</strong></p>
                        <ul class="mb-0">
                            <li>Schwert = W8</li>
                            <li>Kraftbonus = +2</li>
                            <li>Rüstung = 3</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">9. Zielzonen (optional)</h2>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Zone</th>
                                        <th>Malus</th>
                                        <th>Effekt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>Kopf</td><td>-4</td><td>mehr Schaden</td></tr>
                                    <tr><td>Torso</td><td>0</td><td>normal</td></tr>
                                    <tr><td>Arm</td><td>-2</td><td>Gegner verliert Angriff</td></tr>
                                    <tr><td>Bein</td><td>-2</td><td>Gegner langsamer</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="mb-5">
                        <h2 class="h4 fw-bold mb-3">10. Passive Werte</h2>
                        <p>Manchmal muss nicht gewürfelt werden.</p>
                        <p><strong>Passivwert = 10 + Talent</strong></p>
                        <p class="mb-1"><strong>Beispiel:</strong></p>
                        <p class="mb-1">Wahrnehmung = 7</p>
                        <p class="mb-0">Passiv = 17</p>
                    </section>

                    <section class="mb-0">
                        <h2 class="h4 fw-bold mb-3">Kurzform des Systems</h2>
                        <p class="mb-2"><strong>Skillcheck:</strong> W20 + Talent &ge; SG</p>
                        <p class="mb-2"><strong>Kampf:</strong> W20 + Angriff &ge; 10 + Beweglichkeit + Blocken</p>
                        <p class="mb-0"><strong>Schaden:</strong> Waffenschaden + Kraft - Rüstung</p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
