import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import {
    SUBSCRIBE_ONCE_MESSAGE,
    SUBSCRIBE_ONCE_TITLE,
    useSubscribeAccess,
} from "~/composables/useSubscribeAccess";

export default defineNuxtRouteMiddleware(async (to) => {
    const user = useAuthUser();

    if (!user.value?.isEmployee && !user.value?.isSystemOwner) return;

    const { warning } = useToast();
    const { isAgencyOwner, ensureLoaded } = useSubscribeAccess();

    await ensureLoaded();

    if (isAgencyOwner.value) {
        warning(SUBSCRIBE_ONCE_TITLE, SUBSCRIBE_ONCE_MESSAGE);
    } else {
        const role = user.value.isEmployee ? "Employee" : "Platform admin";
        warning("Subscription Access Restricted", `${role} accounts cannot subscribe to a plan, Please use your personal email instead.`);
    }

    if (to.path.endsWith("/subscription-details")) {
        return navigateTo("/product");
    }
});
