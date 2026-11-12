import type { RouteLocationRaw } from "vue-router";
import type { Notification } from "~/types/notification";
import { scheduleService } from "~/api/schedule/ScheduleService";

/** Who the notification is for, which decides where opening it leads. */
export type NotificationAudience = "staff" | "family" | "owner";

// Only families receive these (sent through patient access).
const FAMILY_TYPES: Record<string, string> = {
    Medication: "/portal/medications",
    Vitals: "/portal/medications",
    Update: "/portal/updates",
    "Charge Added": "/portal/balance",
    "Diagnosis Case Charge": "/portal/balance",
    "Discharge Reminder": "/portal/balance",
    Diagnosis: "/portal/loved-ones",
    "Diagnosis case": "/portal/loved-ones",
    "Bed Changed": "/portal/loved-ones",
    "Accommodation Changed": "/portal/loved-ones",
    "Admission Extended": "/portal/loved-ones",
    "Admission Cancelled": "/portal/loved-ones",
};

/**
 * Works out the audience from where the bell (or profile page) was opened.
 * `from` is the profile page's ?from= value ("dashboard" | "owner" | "portal").
 */
export function notificationAudience(
    notif: Notification,
    path: string,
    from?: string | null,
    user?: { isSystemOwner?: boolean; isEmployee?: boolean } | null,
): NotificationAudience {
    if (FAMILY_TYPES[notif.message_type]) return "family";

    if (path.startsWith("/app/owner") || from === "owner") return "owner";
    if (path.startsWith("/app/branches") || from === "dashboard") return "staff";
    if (path.startsWith("/portal") || from === "portal") return "family";

    if (user?.isSystemOwner && notif.message_type === "Subscription") {
        return "owner";
    }

    return user?.isEmployee && notif.branch?.uuid ? "staff" : "family";
}

async function locateSchedule(branchUuid: string, code: string) {
    try {
        const res: any = await scheduleService.action({
            type: "locate",
            branch_uuid: branchUuid,
            schedule_code: code,
        });
        const found = res?.data;

        return {
            type: found?.type ?? "medical",
            ...(found?.date && { date_from: found.date, date_to: found.date }),
            search: code,
        };
    } catch {
        return { search: code };
    }
}

/**
 * The page a notification opens. Null when it has nowhere to go
 * (it is still marked as read by the caller).
 */
export async function resolveNotificationTarget(
    notif: Notification,
    audience: NotificationAudience,
    fallbackBranchUuid?: string | null,
): Promise<RouteLocationRaw | null> {
    const type = notif.message_type;
    const reference = notif.reference_id || undefined;

    if (audience === "owner") {
        return type === "Subscription" ? "/app/owner/verification" : null;
    }

    if (audience === "family") {
        if (FAMILY_TYPES[type]) return FAMILY_TYPES[type];
        if (type === "Booking") {
            return {
                path: "/portal/bookings",
                query: reference ? { reference_id: reference } : {},
            };
        }
        if (type === "Schedule") return "/portal/schedule";
        if (type === "Billing") return "/portal/balance";

        return null;
    }

    // Staff: the branch the notification came from.
    const branch = notif.branch?.uuid || fallbackBranchUuid;
    if (!branch) return null;

    const base = `/app/branches/${branch}`;

    switch (type) {
        case "Booking":
            return {
                path: `${base}/bookings`,
                query: reference ? { reference_id: reference } : {},
            };
        case "Schedule":
            return {
                path: `${base}/schedules`,
                query: reference ? await locateSchedule(branch, reference) : {},
            };
        case "Inquiry":
            return `${base}/inquiries`;
        case "Billing":
            return `${base}/invoices`;
        case "Subscription":
        case "Subscription Renewal":
            return `${base}/manage-subscription`;
        default:
            return null;
    }
}
