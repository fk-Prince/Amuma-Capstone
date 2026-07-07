import { authService } from "~/api/auth/AuthService";
import { useAuthUser, useAuthReady, resetAuth } from "~/composables/useAuthUser";
import { userInitials, avatarSrc } from "~/utils/user";

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
        user.value = res.user;
        if (user.value && !user.value.avatar) {
            const initials = userInitials(user.value);
            user.value.avatar = avatarSrc(initials);
        }
    } catch (err) {
        resetAuth();
    } finally {
        ready.value = true;
    }
    return;
})

