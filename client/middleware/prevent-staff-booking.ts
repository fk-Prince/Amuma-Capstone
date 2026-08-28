import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";

export default defineNuxtRouteMiddleware((to) => {
    const user = useAuthUser();

    if (user.value?.isEmployee || user.value?.isSystemOwner) {
        const { warning } = useToast();
        const role = user.value.isEmployee ? "Employee" : "Platform admin";
        warning("Booking Access Restricted", `${role} accounts cannot create bookings, Please use your personal email instead.`);

        if (to.path.endsWith("/details")) {
            return navigateTo("/");
        }
    }
});
