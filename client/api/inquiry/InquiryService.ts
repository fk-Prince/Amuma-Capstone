import BaseService from '~/api/BaseService';
import type { InquiryStatus } from '~/types/inquiry';

class InquiryService extends BaseService {
    private static instance: InquiryService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): InquiryService {
        if (!InquiryService.instance) {
            InquiryService.instance = new InquiryService();
        }
        return InquiryService.instance;
    }

    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }

    async updateStatus(uuid: string, payload: { branch_uuid: string; status: InquiryStatus }): Promise<any> {
        return await this.request(`${this.resource}/${uuid}/status`, 'PATCH', payload);
    }

    async reply(uuid: string, payload: { branch_uuid: string; reply: string }): Promise<any> {
        return await this.request(`${this.resource}/${uuid}/reply`, 'POST', payload);
    }

    private get resource(): string {
        return `${this.getBackendApi}/api/inquiries`;
    }
}

export const inquiryService = InquiryService.getInstance();
