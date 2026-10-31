import { onBeforeUnmount, watch, type WatchSource } from "vue";
import type { SupportReply, SupportStatus, SupportTicket } from "~/types/support";

export interface SupportTicketUpdate {
    uuid: string;
    status: SupportStatus;
    reply?: SupportReply | null;
}

export const applySupportUpdate = (
    ticket: SupportTicket,
    update: Omit<SupportTicketUpdate, "uuid">,
): SupportTicket => {
    const replies = ticket.replies ?? [];
    const reply = update.reply;
    const isNew = !!reply && !replies.some((item) => item.id === reply.id);

    return {
        ...ticket,
        status: update.status,
        last_reply_at: reply?.created_at ?? ticket.last_reply_at,
        replies: isNew ? [...replies, reply] : replies,
    };
};

export const useSupportTicketLive = (
    ticketUuid: WatchSource<string | null | undefined>,
    onUpdate: (update: SupportTicketUpdate) => void,
) => {
    const { $echo } = useNuxtApp() as any;
    let joined: string | null = null;

    const leave = () => {
        if (!joined || !$echo) return;

        $echo.leave(`SupportTicket.${joined}`);
        joined = null;
    };

    watch(
        ticketUuid,
        (uuid) => {
            if (uuid === joined) return;

            leave();

            if (!uuid || !$echo) return;

            joined = uuid;
            $echo
                .private(`SupportTicket.${uuid}`)
                .listen(".support.updated", onUpdate);
        },
        { immediate: true },
    );

    onBeforeUnmount(leave);
};
