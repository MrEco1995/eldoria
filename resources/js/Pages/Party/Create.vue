<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('parties.store'));
};
</script>

<template>
    <Head title="Party erstellen" />

    <AuthenticatedLayout>
        <template #header>
            <div class="party-create-header">
                <div>
                    <div class="party-create-kicker">Gründungsritus</div>
                    <h2 class="h3 m-0 party-create-title">Neue Party gründen</h2>
                </div>
            </div>
        </template>

        <section class="party-create-scene">
            <div class="party-create-glow party-create-glow-left"></div>
            <div class="party-create-glow party-create-glow-right"></div>

            <div class="row g-4 align-items-stretch position-relative">
                <div class="col-12 col-xl-7">
                    <div class="party-create-card party-create-card-hero h-100">
                        <div class="party-create-body">
                            <div class="party-create-kicker">Einladungshalle</div>
                            <h3 class="party-create-heading">Stelle deine Reisegruppe zusammen</h3>
                            <p class="party-create-copy">
                                Vergib deiner Party einen Namen. Danach erhältst du eine Lobby mit Einladungslink,
                                Teilnehmerübersicht und Charaktererstellung für deine Gruppe.
                            </p>

                            <div class="party-create-feature-list">
                                <div class="party-create-feature">
                                    <div class="party-create-feature-title">30 Minuten gültiger Link</div>
                                    <div class="party-create-feature-copy">Einladungen können später jederzeit neu erzeugt werden.</div>
                                </div>
                                <div class="party-create-feature">
                                    <div class="party-create-feature-title">Bereitschaft im Blick</div>
                                    <div class="party-create-feature-copy">Du siehst sofort, wer bereit ist und wer noch fehlt.</div>
                                </div>
                                <div class="party-create-feature">
                                    <div class="party-create-feature-title">Start direkt aus der Lobby</div>
                                    <div class="party-create-feature-copy">Sobald alle Charaktere stehen, startet der Spielleiter die Sitzung.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-5">
                    <div class="party-create-card h-100">
                        <div class="party-create-body">
                            <div class="party-create-kicker">Namenssiegel</div>
                            <h3 class="party-create-heading">Name der neuen Party</h3>
                            <p class="party-create-copy mb-4">
                                Wähle einen klaren Namen, damit deine Spieler die Gruppe sofort zuordnen können.
                            </p>

                            <form @submit.prevent="submit">
                                <div class="mb-4">
                                    <InputLabel for="name" value="Name der Party" class="party-create-label" />
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        required
                                        autofocus
                                        autocomplete="off"
                                        class="w-100 party-create-input"
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>

                                <div class="d-flex justify-content-end">
                                    <PrimaryButton
                                        :class="['party-create-submit', { disabled: form.processing }]"
                                        :disabled="form.processing"
                                    >
                                        <span v-if="form.processing">Party wird erstellt...</span>
                                        <span v-else>Party erstellen</span>
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>

<style scoped>
.party-create-scene {
    --party-create-bg: linear-gradient(180deg, #08131b 0%, #10202d 52%, #17262c 100%);
    --party-create-card-bg: linear-gradient(180deg, rgba(11, 23, 31, 0.96), rgba(17, 30, 39, 0.93));
    --party-create-border: rgba(206, 175, 111, 0.18);
    --party-create-gold: #d7b168;
    --party-create-text: #eef3ea;
    --party-create-muted: rgba(224, 232, 222, 0.74);
    position: relative;
    overflow: hidden;
    padding: 1.25rem;
    border-radius: 28px;
    background:
        radial-gradient(circle at top left, rgba(116, 207, 198, 0.14), transparent 28%),
        radial-gradient(circle at bottom right, rgba(215, 177, 104, 0.12), transparent 34%),
        var(--party-create-bg);
    box-shadow: 0 24px 56px rgba(4, 10, 16, 0.32);
}

.party-create-header {
    display: flex;
    align-items: center;
}

.party-create-kicker {
    color: #c79e54;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 0.72rem;
    font-weight: 700;
}

.party-create-title {
    color: #15252f;
    font-weight: 700;
}

.party-create-heading {
    margin-bottom: 0.75rem;
    color: var(--party-create-text);
    font-size: 1.55rem;
    font-weight: 700;
}

.party-create-copy,
.party-create-feature-copy {
    color: var(--party-create-muted);
}

.party-create-card {
    position: relative;
    overflow: hidden;
    border-radius: 26px;
    border: 1px solid var(--party-create-border);
    background: var(--party-create-card-bg);
    box-shadow: 0 18px 40px rgba(1, 7, 13, 0.24);
}

.party-create-card-hero {
    background:
        linear-gradient(180deg, rgba(17, 35, 47, 0.96), rgba(15, 24, 33, 0.94)),
        radial-gradient(circle at top left, rgba(108, 210, 199, 0.12), transparent 30%);
}

.party-create-body {
    padding: 1.6rem;
}

.party-create-feature-list {
    display: grid;
    gap: 0.85rem;
    margin-top: 1.5rem;
}

.party-create-feature {
    padding: 1rem;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.035);
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.party-create-feature-title,
.party-create-label {
    color: var(--party-create-text);
    font-weight: 700;
}

.party-create-input :deep(input),
.party-create-input {
    min-height: 48px;
}

.party-create-input {
    border-radius: 16px;
    border: 1px solid rgba(122, 166, 161, 0.2);
    background: rgba(251, 252, 249, 0.96);
}

.party-create-submit {
    border: 0;
    border-radius: 999px;
    background: linear-gradient(135deg, #d7b168, #b98840);
    color: #132029;
    font-weight: 700;
    box-shadow: 0 10px 22px rgba(185, 136, 64, 0.25);
}

.party-create-glow {
    position: absolute;
    border-radius: 999px;
    filter: blur(18px);
    pointer-events: none;
}

.party-create-glow-left {
    top: -36px;
    left: 8%;
    width: 220px;
    height: 180px;
    background: radial-gradient(circle, rgba(100, 203, 192, 0.22), transparent 72%);
}

.party-create-glow-right {
    right: 0;
    bottom: -44px;
    width: 260px;
    height: 220px;
    background: radial-gradient(circle, rgba(215, 177, 104, 0.18), transparent 72%);
}

@media (max-width: 991.98px) {
    .party-create-scene {
        padding: 1rem;
        border-radius: 20px;
    }

    .party-create-body {
        padding: 1.2rem;
    }
}
</style>
