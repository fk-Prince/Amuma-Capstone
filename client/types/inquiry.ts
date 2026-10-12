export type InquiryStatus = "new" | "read" | "resolved";

export interface Inquiry {
    uuid: string;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string;
    phone_number: string;
    city: string | null;
    message: string;
    status: InquiryStatus;
    reply: string | null;
    replied_at: string | null;
    created_at: string;
}

export const INQUIRY_STATUS_LABELS: Record<InquiryStatus, string> = {
    new: "New",
    read: "Read",
    resolved: "Resolved",
};

export const INQUIRY_STATUS_CLASSES: Record<InquiryStatus, string> = {
    new: "bg-primary-100 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300",
    read: "bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
    resolved:
        "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
};
