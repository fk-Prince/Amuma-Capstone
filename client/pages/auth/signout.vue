<template>
    <ClientOnly>
        <AuthTransitionScreen />
    </ClientOnly>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useRoute } from "vue-router";
import AuthTransitionScreen from "~/components/ui/AuthTransitionScreen.vue";
import { authService } from "~/api/auth/AuthService";
import { clearStoredAuth } from "~/composables/useAuthUser";
import { SIGNED_OUT_MESSAGE, useSandbox } from "~/composables/useSandbox";

definePageMeta({
    layout: "auth",
});

useHead({
    title: "Signing out - AMUMA",
});

const route = useRoute();
const { switchUrl } = useSandbox();

function nextUrl() {
    const next = String(route.query.next ?? "");

    if (next.startsWith("/") && !next.startsWith("//")) return next;
    if (switchUrl.value && next.startsWith(`${switchUrl.value}/`)) return next;

    return "/auth/signin";
}

onMounted(async () => {
    try {
        await authService.logout();
    } catch {}

    clearStoredAuth();

    if (route.query.silent && window.parent !== window) {
        try {
            window.parent.postMessage(
                { type: SIGNED_OUT_MESSAGE },
                new URL(switchUrl.value).origin,
            );
        } catch {}
        return;
    }

    window.location.replace(nextUrl());
});
</script>
