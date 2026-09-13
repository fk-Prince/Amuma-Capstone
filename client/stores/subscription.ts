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



        // branch: {
        //     name: "AMUMA Davao City",
        //     contact_number: "9000000000",
        //     image: undefined as any,
        //     description:
        //         "AMUMA Davao City provides compassionate and dependable caregiving services, offering personalized support for daily living, personal care, companionship, and other essential needs.",
        //     location: {
        //         street: "J.P. Laurel Avenue",
        //         city: "Davao City",
        //         province: "Davao del Sur",
        //         country: "Philippines",
        //         latitude: 7.1907,
        //         longitude: 125.4553,
        //     },
        //     email: "davao@amuma.com",
        //     status: "active",
        //     document: ""
        // } as Branch,

        // branch: {
        //     name: "AMUMA Davao City",
        //     contact_number: "9000000000",
        //     image: undefined as any,
        //     description:
        //         "AMUMA Davao City provides compassionate and dependable caregiving services, offering personalized support for daily living, personal care, companionship, and other essential needs.",
        //     location: {
        //         street: "J.P. Laurel Avenue",
        //         city: "Davao City",
        //         province: "Davao del Sur",
        //         country: "Philippines",
        //         latitude: 7.1907,
        //         longitude: 125.4553,
        //     },
        //     email: "davao@amuma.com",
        //     status: "active",
        //     document: ""
        // } as Branch,

        branch: {
            name: "AMUMA Davao City",
            contact_number: "9000000000",
            image: null,
            description:
                "AMUMA Davao City provides compassionate and dependable caregiving services, offering personalized support for daily living, personal care, companionship, and other essential needs.",
            location: {
                street: "J.P. Laurel Avenue",
                city: "Davao City",
                province: "Davao del Sur",
                country: "Philippines",
                latitude: 7.1907,
                longitude: 125.4553,
            },
            email: "davao@amuma.com",
            status: "active",
            document: "",
            is_verified: false,
        } as Branch,

        agency: {
            agency_id: undefined,
            name: "AMUMA Incorporation",
            description:
                "AMUMA Incorporation is a compassionate caregiving agency providing personalized, reliable, and respectful care to individuals and families while promoting dignity, comfort, safety, and independence.",
            location: {
                street: "J.P. Laurel Avenue",
                city: "Davao City",
                province: "Davao del Sur",
                country: "Philippines",
                latitude: 7.1907,
                longitude: 125.4553,
            },
            email: "info@amuma.com",
            image: null,
            is_verified: false,
        } as Agency,



        settings: {
            opening: "00:00",
            closing: "00:00",
            currency: "PHP",
            time_zone: "Asia/Manila",
            reserved_walkin_slots: 3,
            enable_booking_pre_admission: true,
            enable_booking_complete_admission: true,
            requires_full_payment_on_admit: true,
            complete_admission_booking_percent: 100,
            activation_payment_percent: 100,
            minimum_adl_hours: 8,
            is_open: true,
            tin: ""
        } as BranchSettings,

        errors: {},
        subscriptionPayload: null,
    }),

    getters: {
        // The TIN is typed on the branch step but the API carries it inside
        // branch_settings, so every payload has to merge it in. Built here
        // rather than at each call site, which is how it went missing from the
        // submit while the validate step still sent it.
        branchSettingsPayload: (state) => ({
            ...state.settings,
            tin: (state.branch as any)?.tin || null,
        }),

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
                agency_id: undefined,
                name: "",
                description: "",
                location: defaultLocation(),
                email: "",
                image: ""
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
                image: null,
                description: "",
                location: defaultLocation(),
                email: "",
                status: "active",
                is_verified: false,
                agency: this.agency
            } as Branch;

            this.agency = {
                name: "",
                description: "",
                email: "",
                image: null,
                location: defaultLocation(),
                is_verified: false
            } as Agency;


            this.settings = {
                opening: "00:00",
                closing: "00:00",
                currency: "PHP",
                time_zone: "Asia/Manila",
                reserved_walkin_slots: 0,
                enable_booking_pre_admission: true,
                enable_booking_complete_admission: true,
                requires_full_payment_on_admit: true,
                complete_admission_booking_percent: 100,
                activation_payment_percent: 100,
                minimum_adl_hours: 8,
                is_open: true,
                online_additional_fee: 0,
                tin: ""
            } as BranchSettings;
            this.errors = {};
            this.subscriptionPayload = null;
        },
    },
});