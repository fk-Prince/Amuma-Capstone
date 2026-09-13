import { useBranchStore } from "~/stores/branch";


export const handleMenuClick = async (item: any) => {
    const branchStore = useBranchStore();

    if (item.action === "dashboard") {
        if (!branchStore.branches.length) {
            await branchStore.fetchBranches();
        }

        const branches = branchStore.branches.filter((branch) => branch?.uuid);

        // A verified branch actually has a dashboard to land on; an
        // unverified/rejected one only shows a review/rejection screen, so
        // it's picked last rather than first.
        const preferred =
            branchStore.activeBranch ??
            branches.find((branch) => branch.is_verified) ??
            branches[0];

        const uuid = preferred?.uuid;

        if (!uuid) return;

        await navigateTo(`/app/branches/${uuid}/dashboard`);
    } else {
        await navigateTo(item.to);
    }
};

interface ProfileMenuItem {
    icon: string;
    label: string;
    to?: string;
    action?: string;
    types?: string[];
    requires?: string[];
    requiresAny?: string[];
}

export const profileMenuDropDownList: ProfileMenuItem[] = [
    { icon: "user", label: "My profile", to: "/profile" },
    {
        icon: "user",
        label: "Dashboard",
        action: "dashboard",
        types: ["isEmployee"],
    },
    {
        icon: "Family Portal",
        label: "Family Portal",
        to: "/portal/bookings",
        types: ["isClient"],
        requiresAny: ["hasBooking", "hasPatient"],
    },
    {
        icon: "Subscription Management",
        label: "Subscription Management",
        to: "/app/owner/dashboard",
        types: ["isSystemOwner"],
    },
];