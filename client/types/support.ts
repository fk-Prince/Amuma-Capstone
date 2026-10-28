export type SupportStatus = "open" | "answered" | "resolved";

export type SupportCategory =
    | "billing"
    | "verification"
    | "technical"
    | "account"
    | "other";

export interface SupportReply {
    id: number;
    message: string;
    attachment: string | null;
    is_support: boolean;
    author_name: string | null;
    created_at: string;
}

export interface SupportTicket {
    uuid: string;
    ticket_code: string;
    source: "branch" | "public";
    subject: string;
    category: SupportCategory;
    status: SupportStatus;
    created_at: string;
    last_reply_at: string | null;
    submitted_by: { name: string | null; email: string | null };
    branch: { uuid: string | null; name: string | null };
    agency: { name: string | null };
    replies?: SupportReply[];
}

export const SUPPORT_STATUS_LABELS: Record<SupportStatus, string> = {
    open: "Open",
    answered: "Answered",
    resolved: "Resolved",
};

export const SUPPORT_STATUS_CLASSES: Record<SupportStatus, string> = {
    open: "bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
    answered:
        "bg-primary-100 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300",
    resolved:
        "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
};

export const SUPPORT_CATEGORY_LABELS: Record<SupportCategory, string> = {
    billing: "Billing & subscription",
    verification: "Verification",
    technical: "Bug / technical issue",
    account: "Account & access",
    other: "Other",
};

export const SUPPORT_CATEGORY_OPTIONS = (
    Object.keys(SUPPORT_CATEGORY_LABELS) as SupportCategory[]
).map((value) => ({ label: SUPPORT_CATEGORY_LABELS[value], value }));
