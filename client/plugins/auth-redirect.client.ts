import { useAuthUser } from "~/composables/useAuthUser";
import { AUTH_ROUTES, authLandingPath } from "~/utils/authLanding";

// The sign-in pages are rendered on the server, so a signed-in visitor can only
// be moved on once the app has mounted; the route middleware skips that case.
export default defineNuxtPlugin((nuxtApp) => {
    nuxtApp.hook("app:mounted", async () => {
        const route = useRoute();

        if (!AUTH_ROUTES.includes(route.path) || !useAuthUser().value) return;

        await navigateTo(await authLandingPath());
    });
});
