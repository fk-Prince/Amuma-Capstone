// Pure helpers, no imports on purpose: the global middleware loads this file
// eagerly on the server, so it must not pull in anything that could create a
// circular import.

export type LandingAudience = "agency" | "family" | "both";

type RoleFlags = {
    isEmployee?: boolean;
    isSystemOwner?: boolean;
    isClient?: boolean;
    hasBooking?: boolean;
    hasPatient?: boolean;
};

export const AGENCY_LANDING = "/agencies";
export const FAMILY_LANDING = "/families";

/**
 * Who a signed-in account is, for landing-page purposes.
 *  - staff (employee / platform owner)            -> "agency"
 *  - client that already has a booking or patient  -> "family"
 *  - guest, or an account with no role yet         -> "both" (sees either landing)
 */
export const audienceOf = (user: unknown): LandingAudience => {
    const u = user as RoleFlags | null | undefined;

    if (!u) return "both";
    if (u.isEmployee || u.isSystemOwner) return "agency";
    if (u.isClient && (u.hasBooking || u.hasPatient)) return "family";

    return "both";
};

export const landingPathFor = (audience: LandingAudience): string => {
    if (audience === "agency") return AGENCY_LANDING;
    if (audience === "family") return FAMILY_LANDING;
    return "/";
};