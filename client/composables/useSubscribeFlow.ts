import { useAuthUser } from "~/composables/useAuthUser";
import { useSubscriptionCheckout } from "~/stores/subscription";

export const useSubscribeAuthOpen = () =>
    useState<boolean>("subscribe_auth_open", () => false);

export function useSubscribeFlow() {
    const checkout = useSubscriptionCheckout();
    const user = useAuthUser();
    const open = useSubscribeAuthOpen();

    async function startSubscribe(plan: any) {
        checkout.setSelectedPlan(plan);

        if (!user.value) {
            open.value = true;
            return;
        }

        await navigateTo(CHECKOUT_PATH);
    }

    return { startSubscribe, open };
}