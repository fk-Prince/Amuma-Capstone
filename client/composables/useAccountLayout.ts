
import { computed } from "vue";
import { useAuthUser } from "~/composables/useAuthUser";

export const useAccountLayout = () => {
    const user = useAuthUser();

    return computed(() => {
        if (user.value?.isSystemOwner) return "owner";
        if (user.value?.isClient) return "portal";
        return "dashboard";
    });
};