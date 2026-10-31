export const useSupportCenter = () => {
    const state = useState<{ open: boolean; ticketUuid: string | null }>(
        "support-center",
        () => ({ open: false, ticketUuid: null }),
    );

    const openSupport = (ticketUuid: string | null = null) => {
        state.value = { open: true, ticketUuid };
    };

    const closeSupport = () => {
        state.value = { open: false, ticketUuid: null };
    };

    return { state, openSupport, closeSupport };
};
