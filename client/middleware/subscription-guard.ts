import { useSubscriptionCheckout } from "~/stores/subscription";
import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast"


export default defineNuxtRouteMiddleware((to) => {
    const user = useAuthUser();
    const checkout = useSubscriptionCheckout();
    const { error } = useToast();
    if (import.meta.server) return;

    const isLoggedIn = !!user.value;

    if (!isLoggedIn) {
        return navigateTo("/auth/signin");
    }

    // if (user.value?.isEmployee || user.value?.isSystemOwner) {
    //     error(
    //         `Subscription is only available for client accounts. Please use your personal email: ${user.value.email}`,
    //     );
    //     return navigateTo("/product");
    // }


    if (!checkout.selectedPlan) {
        return navigateTo("/product");
    }
});