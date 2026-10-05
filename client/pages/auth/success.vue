<script setup lang="ts">
import { authService } from "~/api/auth/AuthService";

const route = useRoute();
const user = useAuthUser();
const ready = useAuthReady();

onMounted(async () => {
    const token = route.query.token as string;

    if (!token) {
        navigateTo("/auth/signin");
        return;
    }

    localStorage.setItem("auth", token);

    try {
        const res = await authService.me();
        if (!res.user) {
            throw new Error("No active session");
        }

        user.value = res.user;
        navigateTo(consumeAuthRedirect() ?? "/");
    } catch (err: any) {
        console.log(err);
        localStorage.removeItem("auth");
        navigateTo("/auth/signin");
    }
});
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center gap-4">
        <svg
            class="w-10 h-10 animate-spin text-primary"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <path d="M21 12a9 9 0 1 1-6.219-8.56" />
        </svg>
        <p class="text-slate-500 text-sm font-medium">Signing you in…</p>
    </div>
</template>