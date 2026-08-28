export type MessageSender = "client" | "staff";

export type ConversationType = "family" | "staff";

export interface Colleague {
    employee_id: number;
    name: string;
    avatar: string | null;
    role_name: string | null;
    conversation_id: number | null;
}

export interface ConversationSummary {
    conversation_id: number;
    type?: ConversationType;
    branch: {
        branch_id: number | null;
        uuid: string | null;
        name: string | null;
    };
    client_name: string | null;
    staff_name: string | null;
    staff_role: string | null;
    avatar: string | null;
    staff_avatar?: string | null;
    patient_name: string | null;
    patient_names?: string[];
    last_message: string | null;
    last_message_at: string | null;
    unread_count: number;
}

export interface ChatMessage {
    message_id: number;
    sender_type: MessageSender;
    sender_user_id?: number;
    is_mine?: boolean;
    body: string;
    created_at: string | null;
    read_at: string | null;
}

export interface MessageRecipient {
    client_id: number;
    client_name: string;
    avatar: string | null;
    patient_name: string | null;
    patient_names: string[];
    conversation_id: number | null;
}

export interface ConversationThread {
    conversation: ConversationSummary;
    messages: ChatMessage[];
}
