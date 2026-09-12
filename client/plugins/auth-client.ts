import { authService } from "~/api/auth/AuthService";
import { useAuthUser, useAuthReady } from "~/composables/useAuthUser";

export default defineNuxtPlugin(async () => {
    const user = useAuthUser();
    const ready = useAuthReady();

    if (import.meta.server) {
        return;
    }

    ready.value = false;

    // Local reset that only touches refs already obtained above (safe
    // across an `await`, since they're plain reactive objects, not new
    // composable calls) — avoids calling resetAuth() after an await, which
    // internally re-invokes useAuthUser()/useAuthReady() and needs Nuxt's
    // app context to still be attached at that point.
    function clearAuth() {
        user.value = null;
        ready.value = false;
        localStorage.removeItem("auth");
    }

    try {
        if (user?.value) {
            return;
        }

        const res = await authService.me();
        if (!res || !res.user) {
            clearAuth();
            return;
        }
        user.value = res.user;
    } catch (err) {
        clearAuth();
    } finally {
        ready.value = true;
    }
    return;
})