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