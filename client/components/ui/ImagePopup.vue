<template>
    <div
        v-if="image"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
        @click="$emit('close')"
    >
        <img :src="image" class="max-w-5xl max-h-[90vh] rounded-lg shadow-lg" />
    </div>
</template>

<script setup lang="ts">
defineProps<{
    image: string | null;
}>();

defineEmits<{
    (e: "close"): void;
}>();
</script>
