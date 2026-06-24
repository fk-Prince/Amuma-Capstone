<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="isOpen"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            >
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div
                        class="px-6 py-4 border-b flex justify-between items-center"
                    >
                        <h2 class="text-lg font-bold">{{ title }}</h2>
                        <button
                            @click="close"
                            class="text-gray-500 hover:text-gray-700"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div v-for="field in fields" :key="field.name">
                            <label class="block text-sm font-medium mb-1">
                                {{ field.label }}
                            </label>

                            <input
                                v-if="
                                    field.type === 'text' ||
                                    field.type === 'email' ||
                                    field.type === 'number'
                                "
                                v-model="formData[field.name]"
                                :type="field.type"
                                :placeholder="field.placeholder"
                                :required="field.required"
                                class="w-full border rounded-lg px-3 py-2"
                            />

                            <textarea
                                v-else-if="field.type === 'textarea'"
                                v-model="formData[field.name]"
                                :placeholder="field.placeholder"
                                :required="field.required"
                                class="w-full border rounded-lg px-3 py-2"
                            />

                            <select
                                v-else-if="field.type === 'select'"
                                v-model="formData[field.name]"
                                :required="field.required"
                                class="w-full border rounded-lg px-3 py-2"
                            >
                                <option value="">
                                    Select {{ field.label }}
                                </option>
                                <option
                                    v-for="option in field.options"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>

                            <input
                                v-else-if="field.type === 'checkbox'"
                                v-model="formData[field.name]"
                                type="checkbox"
                                class="rounded"
                            />
                        </div>
                        <slot></slot>
                    </div>
                    <div class="px-6 py-4 border-t flex gap-3 justify-end">
                        <button
                            v-for="button in buttons"
                            :key="button.id"
                            @click="handleButtonClick(button)"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium',
                                button.variant === 'primary'
                                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                                    : 'bg-gray-200 text-gray-800 hover:bg-gray-300',
                            ]"
                        >
                            {{ button.label }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, reactive } from "vue";

export interface ModalField {
    name: string;
    label: string;
    type: "text" | "email" | "number" | "textarea" | "select" | "checkbox";
    placeholder?: string;
    required?: boolean;
    options?: { label: string; value: any }[];
}

export interface ModalButton {
    id: string;
    label: string;
    variant?: "primary" | "secondary";
    onClick?: (data: Record<string, any>) => void | Promise<void>;
}

interface Props {
    title: string;
    fields?: ModalField[];
    buttons?: ModalButton[];
    modelValue?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    fields: () => [],
    buttons: () => [
        { id: "cancel", label: "Cancel", variant: "secondary" },
        { id: "confirm", label: "Confirm", variant: "primary" },
    ],
    modelValue: false,
});

const emit = defineEmits<{
    "update:modelValue": [value: boolean];
    "button-click": [button: ModalButton, data: Record<string, any>];
}>();

const isOpen = ref(props.modelValue);

const formData = reactive<Record<string, any>>(() => {
    const data: Record<string, any> = {};
    props.fields.forEach((field) => {
        data[field.name] = field.type === "checkbox" ? false : "";
    });
    return data;
});

const close = () => {
    isOpen.value = false;
    emit("update:modelValue", false);
};

const handleButtonClick = async (button: ModalButton) => {
    if (button.onClick) {
        await button.onClick(formData);
    }
    emit("button-click", button, formData);
    if (button.id === "cancel") {
        close();
    }
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
