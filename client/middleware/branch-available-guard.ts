import { useBranch } from "~/composables/useBranchProvider";
import { useToast } from "~/composables/useToast";

export default defineNuxtRouteMiddleware(async (to) => {
    const uuid = to.params.branch_uuid as string;

    if (!uuid) return;

    const { branch, fetchBranch } = useBranch();

    if (!branch.value || branch.value.uuid !== uuid) {
        try {
            await fetchBranch(uuid);
        } catch {
            return navigateTo("/404");
        }
    }

    if (branch.value?.is_available !== false) return;

    const { warning } = useToast();
    warning(
        "Service Unavailable",
        "This branch isn't accepting bookings right now.",
    );

    return navigateTo(`/booking/provider/${uuid}`);
});
