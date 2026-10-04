import { defineStore } from "pinia";
import { type Agency } from "~/types/agency";
import { type Branch, type BranchSettings } from "~/types/branch";
import { type Subscription } from "~/types/subscription";
import {
    DEFAULT_PLAN_TYPE,
    findPlan,
    planPrice,
    plansOfType,
    type PlanType,
} from "~/utils/planType";

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
        selectedPlanType: DEFAULT_PLAN_TYPE,
        payment_method: "CREDIT-CARD",

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
            status: "pending",
            document: "",
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
            status: "pending",
        } as Agency,


        // branch: {
        //     name: "",
        //     contact_number: "",
        //     image: null,
        //     description: "",
        //     location: {
        //         street: "",
        //         city: "",
        //         province: "",
        //         country: "",
        //     },
        //     email: "",
        //     status: "pending",
        //     document: "",
        // } as Branch,

        // agency: {
        //     agency_id: undefined,
        //     name: "",
        //     description: "",
        //     location: {
        //         street: "",
        //         city: "",
        //         province: "",
        //         country: "",
        //     },
        //     email: "",
        //     image: null,
        //     status: "pending",
        // } as Agency,

        settings: {
            opening: "00:00",
            closing: "23:59",
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
        branchSettingsPayload: (state) => ({
            ...state.settings,
            tin: (state.branch as any)?.tin || null,
        }),

        selectedPrice: (state) =>
            state.selectedPlan ? planPrice(state.selectedPlan) : null,

        typedPlans: (state) => plansOfType(state.plans, state.selectedPlanType),
    },

    actions: {
        setPlans(plans: any[]) {
            this.plans = plans;
            if (plans.length > 0 && !this.selectedPlan) {
                this.selectedPlan = plansOfType(plans, this.selectedPlanType)[0] ?? null;
            }
        },

        setSelectedPlan(plan: any) {
            this.selectedPlan = plan;
            if (plan?.type) this.selectedPlanType = plan.type;
        },

        setSelectedPlanType(type: PlanType) {
            this.selectedPlanType = type;
            this.selectedPlan =
                findPlan(this.plans, this.selectedPlan?.plan_code, type) ??
                plansOfType(this.plans, type)[0] ??
                null;
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
            this.selectedPlanType = DEFAULT_PLAN_TYPE;

            this.branch = {
                name: "",
                contact_number: "",
                image: null,
                description: "",
                location: defaultLocation(),
                email: "",
                status: "pending",
                agency: this.agency
            } as Branch;

            this.agency = {
                name: "",
                description: "",
                email: "",
                image: null,
                location: defaultLocation(),
                status: "pending"
            } as Agency;


            this.settings = {
                opening: "00:00",
                closing: "23:59",
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

    // Keeps the chosen plan through a page reload or the Google sign-in
    // round trip. Session-only, so it is gone when the tab closes.
    persist: {
        pick: ["selectedPlan", "selectedInterval"],
        storage: import.meta.client ? sessionStorage : undefined,
    } as any,
});