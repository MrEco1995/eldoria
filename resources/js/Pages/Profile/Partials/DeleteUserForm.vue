<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="mb-4">
        <header>
            <h2 class="h5">Konto löschen</h2>

            <p class="text-muted mb-3">
                Wenn dein Konto gelöscht wird, werden alle Daten dauerhaft
                entfernt. Lade bitte vorher alle Informationen herunter, die
                du behalten möchtest.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Konto löschen</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-4">
                <h2 class="h5">
                    Bist du sicher, dass du dein Konto löschen willst?
                </h2>

                <p class="text-muted">
                    Nach dem Löschen werden alle Daten dauerhaft entfernt.
                    Bitte gib dein Passwort ein, um das Löschen zu bestätigen.
                </p>

                <div class="mt-3">
                    <InputLabel
                        for="password"
                        value="Passwort"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="w-100"
                        placeholder="Passwort"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" />
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <SecondaryButton @click="closeModal" type="button">
                        Abbrechen
                    </SecondaryButton>

                    <DangerButton
                        :class="{ disabled: form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Konto löschen
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>

