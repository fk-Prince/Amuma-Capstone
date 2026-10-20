export type MessageSender = "client" | "staff";

export type ConversationType = "family" | "staff";

export interface Colleague {
    employee_id: number;
    name: string;
    email?: string | null;
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

export interface ChatAttachment {
    url: string;
    name: string | null;
    type: "image" | "pdf";
}

export interface ChatMessage {
    message_id: number;
    sender_type: MessageSender;
    sender_user_id?: number;
    is_mine?: boolean;
    body: string | null;
    attachment?: ChatAttachment | null;
    preview?: string | null;
    created_at: string | null;
    read_at: string | null;
    is_pinned?: boolean;
    pinned_at?: string | null;
}

export interface OutgoingMessage {
    body: string;
    attachment: File | null;
}

export interface MessageRecipient {
    client_id: number;
    client_name: string;
    email?: string | null;
    avatar: string | null;
    patient_name: string | null;
    patient_names: string[];
    conversation_id: number | null;
}

export interface CareTeamMember {
    employee_id: number;
    name: string;
    role: string | null;
    email: string | null;
    phone: string | null;
    avatar: string | null;
    assigned: boolean;
    conversation_id: number | null;
}

export interface PortalContact {
    patient_id: number;
    patient_name: string;
    branch: {
        branch_id: number | null;
        name: string | null;
    };
    care_team: CareTeamMember[];
}

export interface ConversationThread {
    conversation: ConversationSummary;
    messages: ChatMessage[];
}

export interface DetailsPerson {
    name: string | null;
    role: string | null;
    avatar: string | null;
    email: string | null;
    phone: string | null;
}

export interface DetailsPatient {
    patient_id: number;
    name: string;
    avatar: string | null;
}

export interface DetailsAssignment {
    patient_id: number;
    name: string;
    category: string | null;
    status: string | null;
    /** ISO time of a scheduled visit today, when there is one. */
    time: string | null;
    /** "08:00 - 16:00" for a facility caregiver shift. */
    shift: string | null;
}

export interface DetailsMedia {
    message_id: number;
    url: string;
    name: string | null;
    type: "image" | "pdf";
    is_mine: boolean;
    created_at: string | null;
}

export interface DetailsPinned {
    message_id: number;
    text: string | null;
    is_mine: boolean;
    has_attachment: boolean;
    created_at: string | null;
    pinned_at: string | null;
}

export interface ConversationDetails {
    kind: "family" | "staff";
    person: DetailsPerson;
    branch: string | null;
    /** Family conversations: the loved ones this family member can follow. */
    patients?: DetailsPatient[];
    /** Staff conversations: who this staff member is assigned to today. */
    today?: DetailsAssignment[];
    date?: string;
    /** Every image and file sent in this conversation, newest first. */
    media?: DetailsMedia[];
    /** Pinned messages, most recently pinned first. */
    pinned?: DetailsPinned[];
}