import { computed, toValue, watch, type Component, type MaybeRefOrGetter } from "vue";

import {
    BedDouble,
    BookOpen,
    CalendarCheck2,
    ClipboardList,
    CreditCard,
    FileText,
    HandCoins,
    MessageCircleQuestion,
    MessageSquare,
    Pill,
    QrCode,
    Receipt,
    Settings,
    UserCog,
    UserRound,
    Users,
    Wrench,
} from "lucide-vue-next";
import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";
import { routeAccess } from "~/composables/useRouteAccess";
import { setupAreaOf, useFirstTimeUser, useSetupVisits } from "~/composables/useSetupGuide";
import { useBranchSetup, type BranchSetupStep } from "~/composables/useBranchSetup";
import { useRoute } from "vue-router";
import { isBranchRejected } from "~/utils/branchAccess";


export type SetupContext = "branch" | "portal";

export interface SetupStepsOptions {
    context: MaybeRefOrGetter<SetupContext>;
    branchUuid?: MaybeRefOrGetter<string | undefined>;
}

export interface SetupStep {
    key: string;
    title: string;
    description: string;
    /** What to do on that page, shown in the tip bar there. */
    tip?: string;
    to: string;
    icon: Component;
    done: boolean;
    optional?: boolean;
    action?: string;
}


const TIPS: Record<string, string> = {
    profile: "Add your phone number and a photo, then save.",
    branch: "Open the Branch Information tab, then the Operation Settings tab.",
    services: "Add at least one service with its price so families can book it.",
    rooms: "Create a room, then add at least one bed in it.",
    contracts: "Add at least one care plan with its rate.",
    staff: "Add at least one team member and choose their role and access.",
    subscription: "Check your plan and trial days. You can pay here any time.",
    "admission:bookings": "Open a booking request to approve or decline it.",
    "admission:admissions": "Pick an approved booking and admit the patient to a bed.",
    "admission:rooms": "See which beds are free before you admit someone.",
    inquiries: "Reply to any open inquiry from a family.",
    "nurse:schedules": "Check today's shifts and the patients assigned to you.",
    "caregiver:schedules": "Open a visit and scan its QR code to check in.",
    "branch:medications": "Pick a patient, then mark each dose as given.",
    "branch:patients": "Open a patient to see vitals, diagnoses and care notes.",
    invoices: "Open an invoice to collect a payment or print a receipt.",
    "cashier:bookings": "Downpayments made with new bookings show here.",
    messages: "Pick a conversation or start a new one.",
    "loved-ones": "Open your loved one's card to see their details.",
    schedule: "See what's coming up today and this week.",
    "portal:medications": "Check the latest doses and vital signs.",
    "portal:messages": "Send a message to the branch staff.",
    balance: "See what you owe and pay online.",
};

export function useSetupSteps(options: SetupStepsOptions) {
    const props = {
        get context() {
            return toValue(options.context);
        },
        get branchUuid() {
            return toValue(options.branchUuid);
        },
    };


    const user = useAuthUser();
    const branchStore = useBranchStore();
    const scope = () =>
        props.context === "branch" ? `branch:${props.branchUuid ?? ""}` : props.context;

    const { hasVisited } = useSetupVisits(() => setupAreaOf(scope()));
    const { showFor, skip, markFinished } = useFirstTimeUser();
    const route = useRoute();

    const branchUsable = computed(
        () =>
            props.context !== "branch" ||
            (!!props.branchUuid &&
                branchStore.activeBranch?.uuid === props.branchUuid &&
                !isBranchRejected(branchStore.activeBranch)),
    );

    const branch = computed(() => branchStore.activeBranch);

    const role = computed(() =>
        (branch.value?.role_name ?? "").trim().toLowerCase().replace(/[\s-]+/g, "_"),
    );

    const isSetupRole = computed(
        () => role.value === "agency_owner" || role.value === "branch_manager",
    );

    const required = computed(() => props.context === "branch" && isSetupRole.value);

    const setup = useBranchSetup(() => (required.value ? props.branchUuid : undefined));

    watch(
        () => [required.value, props.branchUuid, route.path] as const,
        ([isRequired]) => {
            if (isRequired) setup.refresh();
        },
        { immediate: true },
    );

    const enabled = computed(() => {
        if (!branchUsable.value) return false;
        if (!required.value) return showFor(scope());
        if (!setup.loaded.value) return false;
        return !setup.complete.value || showFor(scope());
    });

    const base = computed(() => `/app/branches/${props.branchUuid}`);

    const profileLink = computed(() => {
        if (props.context === "portal") return "/profile?tab=profile&from=portal";
        return `/profile?tab=profile&from=dashboard&branch=${props.branchUuid ?? ""}`;
    });

    const profileVisit = computed(() => `/profile/${props.branchUuid ?? ""}`);

    const profileStep = computed<SetupStep>(() => ({
        key: "profile",
        title: "Complete your profile",
        description: "Add your phone number and photo so others know who they're talking to.",
        to: profileLink.value,
        icon: UserRound,
        done:
            props.context === "portal"
                ? !!user.value?.phone_number || hasVisited("/profile")
                : hasVisited(profileVisit.value),
    }));

    const visitStep = (
        key: string,
        path: string,
        title: string,
        description: string,
        icon: Component,
        action?: string,
    ): SetupStep => ({
        key,
        title,
        description,
        to: path,
        icon,
        done: hasVisited(path),
        action,
    });

    function contractsCopy(step: BranchSetupStep) {
        if (step.homecare && step.facility) {
            return "Add at least one homecare plan and one facility plan, with the rates families will pay.";
        }

        return step.facility
            ? "Add at least one facility plan: the monthly or yearly rate for each accommodation."
            : "Add at least one homecare plan: the hourly rate families will pay.";
    }

    function requiredStep(step: BranchSetupStep): SetupStep | null {
        const p = base.value;

        switch (step.key) {
            case "details":
                return {
                    key: "branch",
                    title: "Visit Branch Information and Operation Settings",
                    description: "Check your branch details, then your hours, booking and payment settings.",
                    to: `${p}/settings?tab=${step.visited?.details ? "operation" : "branch"}`,
                    icon: Settings,
                    done: step.done,
                    action: "Open settings",
                };
            case "staff":
                return {
                    key: "staff",
                    title: "Add an employee",
                    description: "Add at least one staff member and choose their role and access.",
                    to: `${p}/employees`,
                    icon: UserCog,
                    done: step.done,
                    action: "Add staff",
                };
            case "services":
                return {
                    key: "services",
                    title: "Add a service",
                    description: "Add at least one care service families can book from this branch.",
                    to: `${p}/services`,
                    icon: Wrench,
                    done: step.done,
                    action: "Add services",
                };
            case "rooms":
                return {
                    key: "rooms",
                    title: "Set up rooms and beds",
                    description: "Add at least one room with a bed so residents can be admitted.",
                    to: `${p}/rooms-beds`,
                    icon: BedDouble,
                    done: step.done,
                    action: "Add rooms",
                };
            case "contracts":
                return {
                    key: "contracts",
                    title: step.homecare && !step.facility
                        ? "Create a homecare plan"
                        : step.facility && !step.homecare
                            ? "Create a facility plan"
                            : "Create your care plans",
                    description: contractsCopy(step),
                    to: `${p}/contracts`,
                    icon: FileText,
                    done: step.done,
                    action: "Add plans",
                };
            case "subscription":
                return {
                    key: "subscription",
                    title: "Check your subscription",
                    description: "See your plan and trial days, and pay whenever you're ready.",
                    to: `${p}/manage-subscription`,
                    icon: CreditCard,
                    done: step.done,
                };
        }

        return null;
    }

    function branchSteps(): SetupStep[] {
        const p = base.value;

        if (isSetupRole.value) {
            return (setup.status.value?.steps ?? [])
                .map(requiredStep)
                .filter((step): step is SetupStep => !!step);
        }

        const byRole: Record<string, SetupStep[]> = {
            admission: [
                visitStep("bookings", `${p}/bookings`, "Review booking requests", "Approve or decline new bookings from families.", BookOpen),
                visitStep("admissions", `${p}/admissions`, "Admit a patient", "Turn an approved booking into an admission and assign a bed.", ClipboardList),
                visitStep("rooms", `${p}/rooms-beds`, "Check rooms and beds", "See which beds are free before you admit someone.", BedDouble),
                visitStep("inquiries", `${p}/inquiries`, "Answer inquiries", "Reply to questions families send before booking.", MessageCircleQuestion),
            ],
            nurse: [
                visitStep("schedules", `${p}/schedules`, "See your schedule", "Your shifts and assigned patients for the day.", CalendarCheck2),
                visitStep("medications", `${p}/medications`, "Record medications (eMAR)", "Mark doses as given and check allergies.", Pill),
                visitStep("patients", `${p}/patients`, "Open a patient's records", "Vitals, diagnoses and care notes in one place.", Users),
            ],
            caregiver: [
                visitStep("schedules", `${p}/schedules`, "See your visits", "Your home visits and shifts, with QR check-in.", QrCode),
                visitStep("medications", `${p}/medications`, "Record medications (eMAR)", "Mark doses as given and check allergies.", Pill),
                visitStep("patients", `${p}/patients`, "Get to know your patients", "Their care needs, notes and activities of daily living.", Users),
            ],
            cashier: [
                visitStep("invoices", `${p}/invoices`, "Open billing and invoices", "Collect payments and print receipts.", Receipt),
                visitStep("bookings", `${p}/bookings`, "Check booking payments", "Downpayments that come with new bookings.", HandCoins),
            ],
        };

        return [
            ...(byRole[role.value] ?? []),
            visitStep("messages", `${p}/messages`, "Message families and staff", "Keep families updated and talk with your team.", MessageSquare),
        ];
    }

    function portalSteps(): SetupStep[] {
        return [
            profileStep.value,
            visitStep("loved-ones", "/portal/loved-ones", "See your loved one's profile", "Their details, room or visit plan, and records.", Users),
            visitStep("schedule", "/portal/schedule", "Check the care schedule", "Upcoming visits, appointments and daily care.", CalendarCheck2),
            visitStep("medications", "/portal/medications", "See medications and vitals", "What was given, when, and the latest vital signs.", Pill),
            visitStep("messages", "/portal/messages", "Message the care team", "Ask questions and get updates from the branch.", MessageSquare),
            visitStep("balance", "/portal/balance", "Review your balance", "Invoices and payments, and pay online.", CreditCard),
        ];
    }

    const steps = computed<SetupStep[]>(() => {
        // Only first-time users get the guide.
        if (!enabled.value) return [];

        const list = props.context === "portal" ? portalSteps() : branchSteps();

        const withTips = list.map((step) => ({
            ...step,
            tip:
                step.tip ??
                TIPS[`${role.value}:${step.key}`] ??
                TIPS[`${props.context}:${step.key}`] ??
                TIPS[step.key],
        }));

        // Only steps this person can actually open.
        if (props.context !== "branch") return withTips;

        return withTips.filter(
            (step) =>
                !step.to.startsWith("/app/branches/") ||
                routeAccess({ path: step.to.split("?")[0], params: { uuid: props.branchUuid } } as any) ===
                "allowed",
        );
    });

    const doneCount = computed(() => steps.value.filter((s) => s.done).length);
    const allDone = computed(() =>
        required.value
            ? setup.complete.value
            : steps.value.length > 0 && doneCount.value === steps.value.length,
    );
    const percent = computed(() =>
        steps.value.length ? Math.round((doneCount.value / steps.value.length) * 100) : 0,
    );
    const canHide = computed(() => !required.value || allDone.value);

    watch(
        allDone,
        (done) => {
            if (done && !required.value) markFinished(scope());
        },
    );

    const firstName = computed(() => user.value?.first_name || "there");

    const heading = computed(() => {
        if (props.context === "portal") return `Welcome, ${firstName.value}! Here's how to get started`;
        if (role.value === "agency_owner") return `Welcome, ${firstName.value}! Let's get your branch ready`;
        if (role.value === "branch_manager") return `Welcome, ${firstName.value}! Finish setting up this branch`;
        return `Welcome, ${firstName.value}! Here's where to start`;
    });

    const subheading = computed(() => {
        if (required.value) {
            return allDone.value
                ? "Your branch is ready. Once your plan is live it shows on the booking page."
                : "Finish every step to go live. Until then the branch stays off the booking page and you can't start your plan early.";
        }
        return "A few quick stops to learn your way around. Each one ticks off once you've opened it.";
    });

    return {
        steps,
        doneCount,
        allDone,
        percent,
        heading,
        subheading,
        role,
        enabled,
        scope,
        required,
        canHide,
        skip: () => {
            if (canHide.value) skip(scope());
        },
    };
}
