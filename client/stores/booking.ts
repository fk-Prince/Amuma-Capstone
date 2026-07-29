import { defineStore } from "pinia";
import type { Service } from "~/types/service";
import type { BranchAvailability, BranchFacility, BranchHomecare } from "~/types/branch";
import type {
    HomecareBooking,
    FacilityBooking,
} from "~/types/booking";
import type {
    Patient,
    Guardian,
    Assessment,
} from "~/types/patient";

export const useBookingStore = defineStore("booking", {
    state: () => ({
        lastSubmittedId: "",
        category: "homecare" as "homecare" | "facility",
        homecare: {} as HomecareBooking,
        facility: {} as FacilityBooking,
        patient: {} as Patient,
        guardian: {} as Guardian,
        assessment: {} as Assessment,

        services: [] as Service[],
        branchHomecare: {} as BranchHomecare,
        branchFacility: [] as BranchFacility[]
    }),
});
