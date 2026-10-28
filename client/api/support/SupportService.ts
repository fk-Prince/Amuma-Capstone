import BaseService from '~/api/BaseService';
import type { SupportCategory } from '~/types/support';

class SupportService extends BaseService {
    private static instance: SupportService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): SupportService {
        if (!SupportService.instance) {
            SupportService.instance = new SupportService();
        }
        return SupportService.instance;
    }

    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }

    async show(uuid: string, branchUuid?: string): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, 'GET', { branch_uuid: branchUuid });
    }

    async create(payload: {
        branch_uuid: string;
        category: SupportCategory;
        subject: string;
        message: string;
        attachment?: File | null;
    }): Promise<any> {
        return await this.request(this.resource, 'POST', payload);
    }

    async createPublic(payload: {
        name?: string;
        email?: string;
        category: SupportCategory;
        subject: string;
        message: string;
        attachment?: File | null;
    }): Promise<any> {
        return await this.request(`${this.resource}/public`, 'POST', payload);
    }

    async reply(uuid: string, payload: { branch_uuid?: string; message: string; attachment?: File | null }): Promise<any> {
        return await this.request(`${this.resource}/${uuid}/replies`, 'POST', payload);
    }

    async updateStatus(uuid: string, payload: { branch_uuid?: string; status: 'open' | 'resolved' }): Promise<any> {
        return await this.request(`${this.resource}/${uuid}/status`, 'PATCH', payload);
    }

    private get resource(): string {
        return `${this.getBackendApi}/api/support-tickets`;
    }
}

export const supportService = SupportService.getInstance();
