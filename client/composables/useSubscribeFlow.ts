import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import {
    SUBSCRIBE_ONCE_MESSAGE,
    SUBSCRIBE_ONCE_TITLE,
    useSubscribeAccess,
} from "~/composables/useSubscribeAccess";
import { useSubscriptionCheckout } from "~/stores/subscription";

export const useSubscribeAuthOpen = () =>
    useState<boolean>("subscribe_auth_open", () => false);

export function useSubscribeFlow() {
    const checkout = useSubscriptionCheckout();
    const user = useAuthUser();
    const open = useSubscribeAuthOpen();
    const { warning } = useToast();
    const { isAgencyOwner, ensureLoaded } = useSubscribeAccess();

    async function startSubscribe(plan: any) {
        if (!user.value) {
            checkout.setSelectedPlan(plan);
            open.value = true;
            return;
        }

        await ensureLoaded();

        if (isAgencyOwner.value) {
            warning(SUBSCRIBE_ONCE_TITLE, SUBSCRIBE_ONCE_MESSAGE);
            return;
        }

        checkout.setSelectedPlan(plan);
        await navigateTo(CHECKOUT_PATH);
    }

    return { startSubscribe, open };
}
