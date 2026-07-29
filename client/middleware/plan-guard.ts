import { useAuthUser } from "~/composables/useAuthUser";


export default defineNuxtRouteMiddleware((to) => {
    const user = useAuthUser();

    if (import.meta.server) return;

    const isLoggedIn = !!user.value;

    if (!isLoggedIn) {
        return navigateTo("/auth/signin");
    }
});