import { useSubscriptionCheckout } from "~/stores/subscription";
import { useAuthUser } from "~/composables/useAuthUser";


export default defineNuxtRouteMiddleware((to) => {
    const user = useAuthUser();
    const checkout = useSubscriptionCheckout();

    if (import.meta.server) return;

    const isLoggedIn = !!user.value;

    if (!isLoggedIn) {
        return navigateTo("/auth/signin");
    }

    const code = to.query.code as string | undefined;
    const interval = to.query.interval as string | undefined;

    // if (!checkout.selectedPlan && (!code || !interval)) {
    //     return navigateTo("/pricing");
    // }
});