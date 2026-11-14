import type { Branch } from "~/types/branch";
import type { PrintBranch } from "~/types/print";

export function printLabel(value?: string | null) {
    if (!value) return "";

    return value
        .replace(/[_-]+/g, " ")
        .toLowerCase()
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

export function printBranchFrom(branch?: Branch | null): PrintBranch {
    return {
        name: branch?.name ?? null,
        image: typeof branch?.image === "string" ? branch.image : null,
        address:
            branch?.location?.full_address ?? branch?.location?.address ?? null,
        contact_number: branch?.contact_number ?? null,
        email: branch?.email ?? null,
        tin: branch?.settings?.tin ?? branch?.tin ?? null,
        agency_name: branch?.agency?.name ?? null,
    };
}
