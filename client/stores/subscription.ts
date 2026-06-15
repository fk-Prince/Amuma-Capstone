import { defineStore } from "pinia";
import { type Agency } from "~/types/agency";
import { type Branch } from "~/types/branch";
import { type Subscription } from "~/types/subscription";

const defaultLocation = () => ({
    address: "",
    street: "",
    city: "",
    province: "",
    country: "",
    lat: 0,
    lng: 0,
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
            image: null,
            description: "",
            location: defaultLocation(),
        } as Branch,
        agency: {
            id: undefined,
            name: "",
            description: "",
            location: defaultLocation(),
        } as Agency,
        settings: {
            opening: "00:00 AM",
            closing: "09:00 PM",
            currency: "PHP",
            online_additional_fee: 0,
            time_zone: "Asia/Manila"
        },
        errors: {},
        subscriptionPayload: null,
    }),

    getters: {
        selectedPrice: (state) => {
            if (!state.selectedPlan) return null;
            return state.selectedInterval === "yearly"
                ? state.selectedPlan.yearly_price?.price
                : state.selectedPlan.monthly_price?.price;
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
            delete this.errors[field];
        },

        clearAgency() {
            this.agency = {
                id: undefined,
                name: "",
                description: "",
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
                image: null,
                description: "",
                location: defaultLocation(),
            } as Branch;

            this.agency = {
                id: undefined,
                name: "",
                description: "",
                location: defaultLocation(),
            } as Agency;

            this.settings = {
                opening: "12:00 AM",
                closing: "12:00 AM",
                currency: "PHP",
            };

            this.errors = {};
            this.subscriptionPayload = null;
        },
    },
});