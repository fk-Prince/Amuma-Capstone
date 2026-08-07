import { defineStore } from "pinia";
import { type Agency } from "~/types/agency";
import { type Branch, type BranchSettings } from "~/types/branch";
import { type Subscription } from "~/types/subscription";

const defaultLocation = () => ({
    address: "",
    street: "",
    city: "",
    province: "",
    country: "",
    latitude: 0,
    longitude: 0,
});

export const useSubscriptionCheckout = defineStore("subscriptionCheckout", {
    state: (): Subscription => ({
        plans: [],
        selectedPlan: null,
        selectedInterval: "",
        payment_method: "CREDIT-CARD",

        branch: {
            name: "",
            contact_number: "",
            image: undefined as any,
            description: "",
            location: defaultLocation(),
        } as Branch,

        agency: {
            id: undefined,
            agency_name: "",
            agency_description: "",
            location: defaultLocation(),
        } as Agency,


        settings: {
            opening: "00:00",
            closing: "00:00",
            currency: "PHP",
            time_zone: "Asia/Manila",
            reserved_walkin_slots: 0,
            enable_booking_pre_admission: true,
            enable_booking_complete_admission: true,
            minimum_adl_hours: 8,
            is_open: false,
        } as BranchSettings,

        errors: {},
        subscriptionPayload: null,
    }),

    getters: {
        selectedPrice: (state) => {
            if (!state.selectedPlan) return null;
            return state.selectedInterval === "yearly"
                ? state.selectedPlan.yearly_price
                : state.selectedPlan.monthly_price;
        },
    },

    actions: {
        setPlans(plans: any[]) {
            this.plans = plans;
            if (plans.length > 0 && !this.selectedPlan) {
                this.selectedPlan = plans[0];
            }
        },

        setSelectedPlan(plan: any) {
            this.selectedPlan = plan;
        },

        setErrors(errors: Record<string, string>) {
            this.errors = errors;
        },

        clearError(field: string) {
            this.errors = Object.fromEntries(
                Object.entries(this.errors).filter(([k]) => k !== field)
            );
        },

        clearAgency() {
            this.agency = {
                id: undefined,
                agency_name: "",
                agency_description: "",
                location: defaultLocation(),
            } as Agency;
        },

        clearAllErrors() {
            this.errors = {};
        },

        reset() {
            this.selectedPlan = null;
            this.selectedInterval = "";

            this.branch = {
                name: "",
                contact_number: "",
                image: undefined as any,
                description: "",
                location: defaultLocation(),
            } as Branch;

            this.agency = {
                id: undefined,
                agency_name: "",
                agency_description: "",
                location: defaultLocation(),
            } as Agency;
            this.settings = {
                opening: "00:00",
                closing: "00:00",
                currency: "PHP",
                time_zone: "Asia/Manila",
                reserved_walkin_slots: 0,
                enable_booking_pre_admission: true,
                enable_booking_complete_admission: true,
                minimum_adl_hours: 8,
                is_open: false,
                online_additional_fee: 0,
            } as BranchSettings;
            this.errors = {};
            this.subscriptionPayload = null;
        },
    },
});