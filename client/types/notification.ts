export interface Notification {
    id: number;
    uuid?: string;
    message_type: string;
    message: string;
    created_at: string;
    unread: boolean;
    icon?: string;
    color?: string;
    bg?: string;
}