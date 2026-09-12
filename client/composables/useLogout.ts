import { ref } from "vue";
import { authService } from "~/api/auth/AuthService";
import { useAuthUser, useAuthReady } from "~/composables/useAuthUser";
import { useSplashScreen } from "~/composables/useSplashScreen";

export function useLogout() {
    const { show } = useSplashScreen();
    const user = useAuthUser();
    const ready = useAuthReady();
    const loading = ref(false);

    async function logout() {
        loading.value = true;
        show({
            title: "Signing you out",
            subtitle: "See you again soon",
        });

        try {
            await authService.logout();
        } catch (err) {
            console.error(err);
        } finally {
            // Reset directly via refs obtained above (before the await),
            // instead of calling resetAuth() here — see auth-client.ts
            // plugin for why that matters.
            user.value = null;
            ready.value = false;
            localStorage.removeItem("auth");

            loading.value = false;
            // Hard navigation, not navigateTo — this fully tears down app
            // state on logout. The splash stays visible through the
            // transition since it's covering the page until it unloads.
            window.location.href = "/auth/signin";
        }
    }

    return { logout, loading };
}