import { stringToDateTime } from "~/utils/time";

export interface AuditLog {
    uuid: string;
    actor_name: string | null;
    actor_email: string | null;
    actor_role: string | null;
    action: string;
    module: string;
    record: string | null;
    description: string | null;
    created_at: string;
}

export interface AuditLogDetail extends AuditLog {
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    user_agent: string | null;
}

export const AUDIT_ACTION_LABELS: Record<string, string> = {
    created: "Created",
    updated: "Updated",
    deleted: "Deleted",
    login: "Login",
    login_failed: "Failed login",
    logout: "Logout",
    exported: "Exported",
};

export const AUDIT_ACTION_CLASSES: Record<string, string> = {
    created:
        "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
    updated:
        "bg-primary-100 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300",
    deleted: "bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300",
    login: "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300",
    login_failed:
        "bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
    logout: "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300",
    exported:
        "bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300",
};

export const AUDIT_MODULE_LABELS: Record<string, string> = {
    auth: "Authentication",
    bookings: "Bookings",
    billing: "Billing",
};

export const AUDIT_ACTION_OPTIONS = Object.entries(AUDIT_ACTION_LABELS).map(
    ([value, label]) => ({ value, label }),
);

export const AUDIT_MODULE_OPTIONS = Object.entries(AUDIT_MODULE_LABELS).map(
    ([value, label]) => ({ value, label }),
);

const capitalize = (value: string) =>
    value.charAt(0).toUpperCase() + value.slice(1);

export const humanize = (value: string): string =>
    capitalize(
        value
            .replace(/([a-z])([A-Z])/g, "$1 $2")
            .replace(/_/g, " ")
            .toLowerCase(),
    );

export const auditActionLabel = (action: string): string =>
    AUDIT_ACTION_LABELS[action] ?? humanize(action);

export const auditModuleLabel = (module: string): string =>
    AUDIT_MODULE_LABELS[module] ?? humanize(module);

export const auditSummary = (log: AuditLog): string => {
    if (log.description) return log.description;

    const record = log.record ? humanize(log.record).toLowerCase() : "";

    return `${auditActionLabel(log.action)}${record ? ` ${record}` : ""}`;
};

const ISO_DATE = /^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2}(:\d{2})?)?/;

export const auditValue = (value: unknown): string => {
    if (value === null || value === undefined || value === "") return "—";
    if (typeof value === "boolean") return value ? "Yes" : "No";
    if (typeof value === "string" && ISO_DATE.test(value)) {
        return stringToDateTime(value) || value;
    }
    if (typeof value === "object") return JSON.stringify(value);

    return String(value);
};
