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


    // if (!checkout.selectedPlan) {
    //     return navigateTo("/product");
    // }
});