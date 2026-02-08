<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="h5">Passwort aktualisieren</h2>
            <p class="text-muted mb-3">
                Verwende ein langes, zufälliges Passwort, um sicher zu bleiben.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-3">
            <div class="mb-3">
                <InputLabel for="current_password" value="Aktuelles Passwort" />

                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                />

                <InputError :message="form.errors.current_password" />
            </div>

            <div class="mb-3">
                <InputLabel for="password" value="Neues Passwort" />

                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                />

                <InputError :message="form.errors.password" />
            </div>

            <div class="mb-3">
                <InputLabel
                    for="password_confirmation"
                    value="Passwort bestaetigen"
                />

                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                />

                <InputError :message="form.errors.password_confirmation" />
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
