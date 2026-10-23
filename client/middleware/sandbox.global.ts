import { useSandbox } from "~/composables/useSandbox";
import { useToast } from "~/composables/useToast";
import { authLandingPath } from "~/utils/authLanding";
import { isSandboxBlockedPath } from "~/utils/sandbox";

export default defineNuxtRouteMiddleware(async (to) => {
    const { isSandbox } = useSandbox();

    if (!isSandbox.value || !isSandboxBlockedPath(to.path)) return;

    if (import.meta.client) {
        useToast().warning(
            "Not available in Sandbox mode",
            "Booking and subscribing are turned off here, since everything in the sandbox is a simulation.",
        );
    }

    return navigateTo(await authLandingPath(), { replace: true });
});
