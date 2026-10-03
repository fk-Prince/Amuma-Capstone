<template>
    <div
        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
    >
        <div class="flex items-start gap-3">
            <div
                v-if="icon"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
            >
                <component :is="icon" class="h-5 w-5" />
            </div>

            <div>
                <h2
                    class="text-lg font-semibold text-slate-900 dark:text-white"
                >
                    {{ title }}
                    <span v-if="required" class="text-danger ml-0.5">*</span>
                </h2>

                <p
                    v-if="description"
                    class="text-sm text-slate-500 dark:text-gray-400"
                >
                    {{ description }}
                </p>

                <slot />
            </div>
        </div>

        <slot name="actions" />
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
    icon?: object;
    required?: boolean;
}>();
</script>
