import { authService } from "~/api/auth/AuthService";
import { useAuthUser, useAuthReady, resetAuth } from "~/composables/useAuthUser";

export default defineNuxtPlugin(async () => {
    const user = useAuthUser();
    const ready = useAuthReady();

    if (import.meta.server) {
        return;
    }

    ready.value = false;

    try {
        if (user?.value) {
            return;
        }

        const res = await authService.me();
        if (!res || !res.user) {
            resetAuth();
            return;
        }
        user.value = res.user;
    } catch (err) {
        resetAuth();
    } finally {
        ready.value = true;
    }
    return;
})

