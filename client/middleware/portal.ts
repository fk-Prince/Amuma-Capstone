import { useAuthUser } from "~/composables/useAuthUser";

export default defineNuxtRouteMiddleware((to) => {
    if (import.meta.server) return;

    const user = useAuthUser();

    if (!user.value) {
        return navigateTo({
            path: "/unauthenticated",
            query: { redirect: to.fullPath },
        });
    }

    if (!user.value.isClient) {
        return navigateTo({
            path: "/unauthenticated",
            query: { redirect: to.fullPath },
        });
    }
});
