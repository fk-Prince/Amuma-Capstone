export interface Notification {
    id: number;
    uuid?: string;
    message_type: string;
    /** Booking notifications carry the booking's reference id. */
    reference_id?: string | null;
    message: string;
    created_at: string;
    unread: boolean;
    /** Null for portal/client notifications, which carry no branch context. */
    branch?: {
        uuid: string | null;
        name: string | null;
        image?: string | null;
    } | null;
    icon?: string;
    color?: string;
    bg?: string;
    data?: any;
}