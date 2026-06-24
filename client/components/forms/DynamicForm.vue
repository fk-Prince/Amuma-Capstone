<template>
    <div class="bg-white rounded-2xl shadow p-6">
        <button
            class="w-full flex justify-between items-center font-bold text-lg"
            @click="show = !show"
        >
            <span>{{ title }}</span>
        </button>

        <div v-if="show" class="mt-5 space-y-4">
            <BaseInput
                v-for="field in fields"
                :key="field.key"
                :label="field.label"
                :type="field.type || 'text'"
                :placeholder="field.placeholder || field.label"
                :modelValue="modelValue[field.key]"
                @update:modelValue="updateField(field.key, $event)"
            />
        </div>
    </div>
</template>
<script setup>
import { ref, computed } from "vue";
import BaseInput from "@/components/ui/BaseInput.vue";

const props = defineProps({
    title: String,
    fields: Array,
    modelValue: Object,
});
const emit = defineEmits(["update:modelValue"]);

const show = ref(true);

const form = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

const updateField = (key, value) => {
    form.value = {
        ...form.value,
        [key]: value,
    };
};
</script>
