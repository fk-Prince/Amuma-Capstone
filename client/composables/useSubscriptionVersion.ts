export const useSubscriptionVersion = () =>
    useState<number>("subscription_version", () => 0);
