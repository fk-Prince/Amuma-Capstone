import { useAuthUser } from "~/composables/useAuthUser";

export const usePermissions = () => {
    const user = useAuthUser();

    const hasRole = (...roles: string[]) => {
        return (
            user.value?.roles?.some((r: any) =>
                roles.includes(r.role)
            ) ?? false
        );
    };
    return {
        hasRole,
    };
};