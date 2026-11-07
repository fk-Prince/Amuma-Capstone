type BranchLike = {
    status?: string | null;
    subscription_status?: string | null;
    agency?: { status?: string | null } | null;
} | null | undefined;

export const isBranchRejected = (branch: BranchLike): boolean =>
    branch?.status === "rejected" ||
    branch?.agency?.status === "rejected" ||
    branch?.subscription_status === "rejected";
