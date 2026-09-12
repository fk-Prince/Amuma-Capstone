
import type { User } from "~/types/auth";
import { userService } from "~/api/user/UserService";
import { useBranchStore } from "~/stores/branch";

export async function getPostLoginRoute(user: User): Promise<string> {
    if (user.isSystemOwner) {
        return "/app/owner/dashboard";
    }

    if (user.isClient) {
        return "/portal/overview";
    }

    const branchStore = useBranchStore();

    try {
        const res = await userService.userBranch();
        const branches = res.data?.branches ?? [];
        branchStore.branches = branches;

        const uuid = branches[0]?.uuid;
        return uuid ? `/app/branches/${uuid}/dashboard` : "/";
    } catch (err) {
        console.error(err);
        return "/";
    }
}