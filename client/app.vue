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
import { onMounted, ref } from "vue";
import { registerToast } from "@/composables/useToast";
import { useRoute, navigateTo } from "#imports";

const toastRef = ref();

onMounted(() => {
    registerToast(toastRef.value);
});

const route = useRoute();

onMounted(() => {
    const token = route.query.token as string;
    if (token) {
        localStorage.setItem("auth", token);
        window.history.replaceState({}, "", "/");
        navigateTo("/");
    }
});
</script>
