<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Passwort vergessen" />

        <div class="position-relative mb-4">
            <div class="d-flex align-items-center gap-3">
                <div
                    class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white"
                    style="width: 56px; height: 56px; box-shadow: 0 10px 30px rgba(107,92,255,0.25);"
                >
                    <ApplicationLogo style="width: 40px; height: 40px;" />
                </div>
                <div>
                    <div class="text-uppercase small text-muted" style="letter-spacing: 2px;">
                        Support
                    </div>
                    <h2 class="h4 mb-1">Passwort vergessen?</h2>
                    <p class="text-muted mb-0">Wir senden dir einen Link zum Zurücksetzen.</p>
                </div>
            </div>
        </div>

        <div v-if="status" class="alert alert-success border-0">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="position-relative" style="padding:10px">
            <div
                class="position-absolute top-0 start-0 w-100 h-100 rounded-4"
                style="z-index: 0; background: radial-gradient(120% 80% at 20% 10%, rgba(107,92,255,0.15), rgba(77,208,225,0.08) 40%, rgba(255,255,255,0) 70%); box-shadow: inset 0 0 60px rgba(107,92,255,0.08);"
            ></div>
            <div class="position-relative" style="z-index: 1;">
            <div class="mb-3">
                <InputLabel for="email" value="E-Mail" />

                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError :message="form.errors.email" />
            </div>

            <div class="d-flex justify-content-end">
                <PrimaryButton
                    :class="{ disabled: form.processing }"
                    :disabled="form.processing"
                >
                    Reset-Link senden
                </PrimaryButton>
            </div>
            </div>
        </form>
    </GuestLayout>
</template>
