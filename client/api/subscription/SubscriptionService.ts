import BaseService from '~/api/BaseService';
import type { SubscriptionRequest } from '~/types/subscription';


class SubscriptionService extends BaseService {
    private static instance: SubscriptionService;

    public static getInstance(): SubscriptionService {
        if (!SubscriptionService.instance) {
            SubscriptionService.instance = new SubscriptionService();
        }
        return SubscriptionService.instance;
    }

    async createSubscription(payload: SubscriptionRequest | any): Promise<any> {
        return await this.request(this.resource, 'POST', payload);
    }

    async retrieveSubscriptionDetail(payload: SubscriptionRequest): Promise<any> {
        return await this.request(this.resource + '-detail', 'GET', payload);
    }

    async validateSubscription(payload: SubscriptionRequest | any) {
        return await this.request(this.resource + '-validate', 'POST', payload);
    }

    private get resource(): string {
        const config = useRuntimeConfig();
        return `${config.public.backendApi}/api/subscription`;
    }
}

export const subscriptionService = SubscriptionService.getInstance();

