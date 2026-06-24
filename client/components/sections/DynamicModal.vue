<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="$emit('close')"
    >
        <div
            class="bg-white rounded-lg p-6 shadow-lg w-full"
            :class="className"
        >
            <div class="grid grid-cols-3 items-center mb-4">
                <div></div>

                <h2 class="text-2xl uppercase font-bold text-center py-5">
                    {{ title }}
                </h2>

                <div class="flex justify-end">
                    <button
                        @click="$emit('close')"
                        class="text-gray-500 hover:text-gray-800 text-lg leading-none"
                    >
                        ✕
                    </button>
                </div>
            </div>
            <form @submit.prevent="submit">
                <div class="space-y-4">
                    <div v-for="input in inputs" :key="input.name">
                        <BaseInput
                            v-if="input.type !== 'combobox'"
                            v-model="form[input.name]"
                            :label="input.label"
                            :placeholder="input.placeholder"
                            :mode="input.mode"
                            :error="errors?.[input.name]?.[0]"
                            :required="input.required"
                        />

                        <Combobox
                            v-else
                            v-model="form[input.name]"
                            :label="input.label"
                            :items="input.items || []"
                            :placeholder="input.placeholder"
                            :allowCustom="input.allowCustom"
                            :error="errors?.[input.name]?.[0]"
                            :required="input.required"
                            searchBar
                        />
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 bg-gray-200 rounded"
                    >
                        Close
                    </button>

                    <button
                        type="submit"
                        class="px-4 py-2 bg-primary text-white rounded"
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, watch } from "vue";
import BaseInput from "../ui/BaseInput.vue";
import Combobox from "../ui/Combobox.vue";

type InputField = {
    type?: "input" | "combobox";
    name: string;
    label: string;
    placeholder?: string;
    mode?: string;
    required?: boolean;
    allowCustom?: boolean;
    items?: {
        label: string;
        value: any;
        icon?: string;
    }[];
};

const props = defineProps<{
    title: string;
    inputs: InputField[];
    className?: string;
    errors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
    close: [];
    save: [data: Record<string, any>];
}>();

const form = reactive<Record<string, any>>({});

watch(
    () => props.inputs,
    (inputs) => {
        inputs.forEach((input) => {
            form[input.name] = "";
        });
    },
    { immediate: true },
);

const submit = () => {
    emit("save", { ...form });
};
</script>
