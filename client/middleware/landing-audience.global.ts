import {
    AGENCY_LANDING,
    FAMILY_LANDING,
    audienceOf,
    landingPathFor,
} from "~/utils/landingAudience";

const LANDING_PATHS = ["/", AGENCY_LANDING, FAMILY_LANDING];

// Plan-picking / checkout pages only. The payment-return pages are left out on
// purpose so an in-flight payment is never interrupted.
const SUBSCRIPTION_PATHS = [
    "/product",
    "/product/subscription-details",
    "/product/subscription-summary",
];

const isSubscriptionPath = (path: string) => {
    const clean = path.length > 1 ? path.replace(/\/+$/, "") : path;

    return SUBSCRIPTION_PATHS.some(
        (p) => clean === p || clean.startsWith(`${p}/`),
    );
};

export default defineNuxtRouteMiddleware(async (to) => {
    // Auth state only exists in the browser.
    if (import.meta.server) return;

    // Same key as composables/useAuthUser.ts. Read directly so this global
    // middleware has no heavy imports (they caused a circular-import 500).
    const user = useState<unknown>("auth_user", () => null);
    const audience = audienceOf(user.value);

    // Guests and accounts with no role yet can see both landing pages.
    if (audience === "both") return;

    const home = landingPathFor(audience);

    // "/", "/agencies" and "/families" all resolve to the visitor's own landing.
    if (LANDING_PATHS.includes(to.path)) {
        if (to.path === home) return;
        return navigateTo(home, { replace: true });
    }

    // Families book care, so subscription pages are off limits for them.
    if (audience === "family" && isSubscriptionPath(to.path)) {
        const { useToast } = await import("~/composables/useToast");
        useToast().warning(
            "Plans are for agencies",
            "Subscriptions are only for agency accounts. You can book care from your family account.",
        );

        return navigateTo(home, { replace: true });
    }
});