<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="h5">Profilinformationen</h2>
            <p class="text-muted mb-3">
                Aktualisiere deine Profilinformationen und E-Mail-Adresse.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-3"
        >
            <div class="mb-3">
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError :message="form.errors.name" />
            </div>

            <div class="mb-3">
                <InputLabel for="email" value="E-Mail" />

                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    readonly
                    autocomplete="username"
                />

                <InputError :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="text-muted">
                    Deine E-Mail-Adresse ist nicht bestaetigt.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="btn btn-link p-0 align-baseline"
                    >
                        Klicke hier, um die Bestaetigungs-E-Mail erneut zu senden.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="alert alert-success py-2"
                >
                    Ein neuer Bestaetigungslink wurde an deine E-Mail-Adresse gesendet.
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <PrimaryButton :disabled="form.processing">Speichern</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-muted mb-0">
                        Gespeichert.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
