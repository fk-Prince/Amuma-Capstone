<script setup lang="ts">
import { reactive } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import type { RoomForm } from "~/types/room";
import Combobox from "~/components/ui/Combobox.vue";

const emit = defineEmits<{
    close: [];
    submit: [];
}>();

const props = defineProps<{
    form: RoomForm;
    title?: string;
    subtitle?: string;
    buttonTitle?: string;
    submitLoading?: boolean;
    errors: any;
}>();

const onSubmit = () => {
    emit("submit");
};
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            @click.self="emit('close')"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-6 py-5"
                >
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ title }}
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ subtitle }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="emit('close')"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M18 6L6 18M6 6l12 12"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="space-y-5 p-6">
                    <BaseInput
                        v-model="form.room_no"
                        :error="errors?.room_no"
                        placeholder="Room 101"
                        label="Room Number / Name"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <BaseInput
                            v-model="form.floor"
                            :error="errors?.floor"
                            type="text"
                            placeholder="1st"
                            label="Floor"
                        />

                        <BaseInput
                            v-model="form.capacity"
                            :error="errors?.capacity"
                            type="number"
                            min="1"
                            label="Capacity"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <Combobox
                            v-model="form.room_type"
                            :error="errors?.room_type"
                            label="Room Type"
                            :items="[
                                {
                                    label: 'Common',
                                    value: 'Common',
                                },
                                {
                                    label: 'VIP',
                                    value: 'VIP',
                                },
                            ]"
                        />
                        <Combobox
                            v-model="form.status"
                            :error="errors?.status"
                            label="Status"
                            :items="[
                                {
                                    label: 'Available',
                                    value: 'Available',
                                },
                                {
                                    label: 'Occupied',
                                    value: 'Occupied',
                                },
                                {
                                    label: 'Maintenance',
                                    value: 'Maintenance',
                                },
                            ]"
                        />
                    </div>

                    <div
                        class="flex justify-end gap-3 border-t border-gray-100 pt-5"
                    >
                        <button
                            type="button"
                            @click="emit('close')"
                            class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="submitLoading"
                            class="inline-flex min-w-[140px] items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <svg
                                v-if="submitLoading"
                                class="mr-2 h-4 w-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-90"
                                    fill="currentColor"
                                    d="M22 12a10 10 0 0 0-10-10v4a6 6 0 0 1 6 6h4z"
                                />
                            </svg>

                            <span>
                                {{ submitLoading ? "Saving..." : buttonTitle }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
