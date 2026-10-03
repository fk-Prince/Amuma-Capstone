import { useAuthUser, fetchAuthUser } from "~/composables/useAuthUser";

export default defineNuxtRouteMiddleware(async (to) => {
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

    // The signed-in user is cached, so a booking made a moment ago may not be
    // reflected yet. Ask the server before telling someone they have none.
    if (!user.value.hasBooking && !user.value.hasPatient) {
        await fetchAuthUser().catch(() => {});

        if (!user.value) {
            return navigateTo({
                path: "/unauthenticated",
                query: { redirect: to.fullPath },
            });
        }
    }

    if (!user.value.hasBooking && !user.value.hasPatient) {
        return navigateTo({
            path: "/unauthenticated",
            query: { redirect: to.fullPath, reason: "no-booking" },
        });
    }
});
