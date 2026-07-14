<template>
    <div v-if="setting" class="space-y-6 w-full">
        <SubcriptionConfigure
            v-model:setting="setting"
            v-model:errors="errors"
        />
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import SubcriptionConfigure from "~/components/forms/SubcriptionConfigure.vue";

const setting = defineModel<any>("setting");

const errors = defineModel<Record<string, string>>("errors", {
    default: () => ({}),
});

const saving = ref(false);

const emit = defineEmits<{
    (e: "save"): void;
}>();
</script>
