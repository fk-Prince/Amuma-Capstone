import { driver, type DriveStep } from "driver.js";
import "driver.js/dist/driver.css";
import "~/assets/css/tour.css";
import type { Ref } from "vue";
import { userService } from "~/api/user/UserService";
import { useAuthUser } from "~/composables/useAuthUser";

const navSteps = [
    { to: "/portal/bookings", title: "Booking", description: "Track your booking requests, service details and status." },
    { to: "/portal/overview", title: "Overview", description: "A quick look at how your loved one is doing today." },
    { to: "/portal/loved-ones", title: "My Loved Ones", description: "View your loved one's profile and records." },
    { to: "/portal/schedule", title: "Schedule", description: "See upcoming appointments and the daily care schedule." },
    { to: "/portal/medications", title: "Medications", description: "Medication schedules, vital signs and care instructions." },
    { to: "/portal/messages", title: "Messages", description: "Chat directly with your loved one's care provider." },
    { to: "/portal/updates", title: "Updates", description: "Latest activities, care notes and appointments." },
    { to: "/portal/balance", title: "Balance", description: "Invoices, payments, balances and refunds for your loved ones." },
    { to: "/profile", title: "My Profile", description: "Update your personal details and password." },
];

const isVisible = (selector: string) => {
    const el = document.querySelector<HTMLElement>(selector);
    return !!el && el.getClientRects().length > 0;
};

export const usePortalTour = (sidebarOpen: Ref<boolean>) => {
    const user = useAuthUser();

    const complete = async () => {
        if (!user.value) return;

        user.value = {
            ...user.value,
            onboarding: {
                ...user.value.onboarding,
                portal: { ...user.value.onboarding?.portal, main: new Date().toISOString() },
            },
        };

        try {
            const res = await userService.completeOnboarding("portal");
            if (res?.data?.onboarding && user.value) {
                user.value = { ...user.value, onboarding: res.data.onboarding };
            }
        } catch {}
    };

    const start = async () => {
        const mobile = window.matchMedia("(max-width: 1023px)").matches;

        if (mobile) {
            sidebarOpen.value = true;
            await new Promise((resolve) => setTimeout(resolve, 250));
        }

        const steps: DriveStep[] = [
            {
                element: '[data-tour="portal-sidebar"]',
                popover: {
                    title: "Welcome to your family portal",
                    description: "Here's a quick look at where everything is. Everything you need is in this menu.",
                    side: "right",
                    align: "start",
                },
            },
            ...navSteps
                .map((step) => ({ ...step, selector: `[data-tour="portal-sidebar"] a[href^="${step.to}"]` }))
                .filter((step) => isVisible(step.selector))
                .map((step) => ({
                    element: step.selector,
                    popover: { title: step.title, description: step.description, side: "right", align: "center" } as const,
                })),
        ];

        const tour = driver({
            steps,
            showProgress: true,
            progressText: "{{current}} of {{total}}",
            nextBtnText: "Next",
            prevBtnText: "Back",
            doneBtnText: "Finish",
            popoverClass: "amuma-tour",
            stagePadding: 4,
            stageRadius: 12,
            overlayClickBehavior: () => {},
            onDestroyed: () => {
                if (mobile) sidebarOpen.value = false;
                complete();
            },
        });

        tour.drive();
    };

    return { start };
};
