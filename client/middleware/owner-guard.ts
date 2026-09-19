import { useAuthUser } from "~/composables/useAuthUser";

export default defineNuxtRouteMiddleware((to) => {
    if (import.meta.server) return;

    const user = useAuthUser();

    if (!user.value?.isSystemOwner) {
        return navigateTo({
            path: "/unauthenticated",
            query: { redirect: to.fullPath, reason: "not-admin" },
        });
    }
});
