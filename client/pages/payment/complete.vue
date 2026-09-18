<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center gap-2 bg-white px-6 text-center font-primary dark:bg-secondary"
    >
        <p class="text-base font-semibold text-slate-800 dark:text-white">
            {{ isSuccess ? "Payment received" : "Payment not completed" }}
        </p>

        <p class="text-sm text-slate-500 dark:text-gray-400">
            {{
                isSuccess
                    ? "You can close this window."
                    : "No money was taken. You can close this window and try again."
            }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { useRoute } from "vue-router";

definePageMeta({ layout: false });
useHead({ title: "Payment" });

const route = useRoute();

const isSuccess = computed(() => route.query.status === "success");

onMounted(() => {
    if (window.parent === window) return;

    window.parent.postMessage(
        { status: isSuccess.value ? "success" : "failed" },
        window.location.origin,
    );
});
</script>
