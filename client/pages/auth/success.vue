<script setup lang="ts">
import { authService } from "~/api/auth/AuthService";
import { useSplashScreen } from "~/composables/useSplashScreen";
import { getPostLoginRoute } from "~/composables/usePostLoginRoute";

const route = useRoute();
const user = useAuthUser();
const ready = useAuthReady();
const splash = useSplashScreen();

splash.show({
    title: "Signing you in",
    subtitle: "Just a moment while we verify your account…",
});

onMounted(async () => {
    const token = route.query.token as string;

    if (!token) {
        splash.hide();
        navigateTo("/auth/signin");
        return;
    }

    localStorage.setItem("auth", token);

    try {
        const res = await authService.me();
        user.value = res;

        splash.show({
            title: `Welcome back, ${res?.first_name ?? "there"}!`,
            subtitle: "Taking you to your dashboard…",
        });

        const destination = await getPostLoginRoute(res);

        setTimeout(async () => {
            await navigateTo(destination);
            setTimeout(() => splash.hide(), 500);
        }, 500);
    } catch (err: any) {
        console.log(err);
        localStorage.removeItem("auth");
        splash.hide();
        navigateTo("/auth/signin");
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#EEF3FB]" />
</template>