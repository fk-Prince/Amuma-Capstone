import { authService } from "~/api/auth/AuthService"
import type { User } from "~/types/auth"

export const useAuthUser = () =>
    useState<User | null>("auth_user", () => null)
export const useAuthReady = () => useState("auth_ready", () => false)

export const resetAuth = () => {
    const user = useAuthUser()
    const ready = useAuthReady()

    user.value = null
    ready.value = false

    localStorage.removeItem("auth");
}

export const fetchAuthUser = async () => {
    const user = useAuthUser();
    const ready = useAuthReady();

    try {
        ready.value = false;
        const res = await authService.me();
        user.value = res.user;
        return res.user;
    } catch (err) {
        resetAuth();
        throw err;
    } finally {
        ready.value = true;
    }
};