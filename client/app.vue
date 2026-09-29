<template>
    <div>
        <NuxtLayout class="font-sans m-0 p-0">
            <NuxtPage />
        </NuxtLayout>
        <ClientOnly>
            <AppToast ref="toastRef" />
        </ClientOnly>
    </div>
</template>

<script setup lang="ts">
import AppToast from "./components/ui/AppToast.vue";
import { ref, watch } from "vue";
import { registerToast } from "@/composables/useToast";

const toastRef = ref();

watch(
    toastRef,
    (instance) => {
        if (instance) registerToast(instance);
    },
    { immediate: true },
);
</script>
