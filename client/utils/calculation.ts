import type { BranchFacility } from "~/types/branch";

export function getAnnualDiscount(
    facilities: BranchFacility[],
    accommodationType: string,
): number {
    const monthly = Number(
        facilities.find(
            (item) =>
                (item.accommodation_type || "").toUpperCase() ===
                accommodationType.toUpperCase() &&
                (item.billing_cycle || "").toUpperCase() === "MONTHLY",
        )?.price ?? 0,
    );

    const yearly = Number(
        facilities.find(
            (item) =>
                (item.accommodation_type || "").toUpperCase() ===
                accommodationType.toUpperCase() &&
                (item.billing_cycle || "").toUpperCase() === "YEARLY",
        )?.price ?? 0,
    );

    if (monthly <= 0 || yearly <= 0) return 0;

    const fullYear = monthly * 12;

    return Math.max(
        0,
        Math.round(((fullYear - yearly) / fullYear) * 100),
    );
}

export function getFacilityPrice(
    facilities: BranchFacility[],
    billingCycle: "Monthly" | "Yearly",
    accommodationType: string,
): string {
    const facility = facilities.find(
        (item) =>
            (item.accommodation_type || "").toUpperCase() ===
            accommodationType.toUpperCase() &&
            (item.billing_cycle || "").toUpperCase() ===
            billingCycle.toUpperCase(),
    );

    const price = Number(facility?.price ?? 0);

    return isNaN(price) ? "0" : price.toLocaleString();
}