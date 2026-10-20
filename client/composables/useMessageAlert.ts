import { useRoute, useRouter } from "vue-router";
import { useToast } from "~/composables/useToast";
import { playMessageSound } from "~/composables/useSounds";

// One place that handles "a new message just arrived": plays the message
// sound (if it is switched on) and shows a clickable "New message" popup.
// Call useMessageAlert() once in a component's setup, then call
// notifyNewMessage(payload, senderName) from the message listener.
export function useMessageAlert() {
    const route = useRoute();
    const router = useRouter();
    const { message } = useToast();

    // Where the popup takes you when it is clicked.
    function messagesPath(): string | null {
        if (route.path.startsWith("/portal")) return "/portal/messages";

        const uuid = route.params.uuid;

        if (route.path.startsWith("/app/branches") && typeof uuid === "string") {
            return `/app/branches/${uuid}/messages`;
        }

        return null;
    }

    function notifyNewMessage(payload: any, senderName?: string | null) {
        playMessageSound();

        // Already on a messages page, the list and the thread update live.
        if (route.path.includes("/messages")) return;

        const raw = String(payload?.preview ?? payload?.body ?? "").trim();
        const hasAttachment =
            !!payload?.attachment || !!payload?.attachments?.length;
        const text = raw || (hasAttachment ? "Sent an attachment" : "");
        const short = text.length > 90 ? `${text.slice(0, 87).trimEnd()}...` : text;

        const target = messagesPath();

        message(
            senderName ? `New message from ${senderName}` : "New message",
            short || undefined,
            target ? () => router.push(target) : undefined,
        );
    }

    return { notifyNewMessage };
}