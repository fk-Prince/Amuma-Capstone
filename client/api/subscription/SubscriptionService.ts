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

    async renew(payload: any): Promise<any> {
        return await this.request(this.resource + '-renew', 'POST', payload);
    }

    async createBranchFromCapacity(payload: any): Promise<any> {
        return await this.request(this.resource + '-branch', 'POST', payload);
    }

    async applyUpgrade(payload: any): Promise<any> {
        return await this.request(this.resource + '-apply-upgrade', 'POST', payload);
    }


    async list(payload: any): Promise<any> {
        return await this.request(this.resource, 'GET', payload);
    }

    async action(payload: any): Promise<any> {
        return await this.request(this.resource + '/action', 'POST', payload);
    }

    private get resource(): string {
        const config = useRuntimeConfig();
        return `${config.public.backendApi}/api/subscriptions`;
    }
}

export const subscriptionService = SubscriptionService.getInstance();

