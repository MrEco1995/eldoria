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
            <h2 class="h4 m-0">Party erstellen</h2>
        </template>

        <div class="row position-relative">
            <div
                class="position-absolute top-0 start-50 translate-middle-x"
                style="width: 360px; height: 180px; background: radial-gradient(circle, rgba(77,208,225,0.35), rgba(77,208,225,0)); filter: blur(6px);"
            ></div>
            <div class="col-12 col-lg-6 position-relative">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-uppercase small text-muted mb-2" style="letter-spacing: 2px;">
                            Einladungen
                        </div>
                        <h5 class="card-title mb-2">Neue Party</h5>
                        <p class="text-muted mb-4">
                            Der Einladungslink ist 30 Minuten gueltig und kann spaeter neu erstellt werden.
                        </p>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <InputLabel for="name" value="Name der Party" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="off"
                                    class="w-100"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="d-flex justify-content-end">
                                <PrimaryButton
                                    :class="{ disabled: form.processing }"
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
    </AuthenticatedLayout>
</template>
