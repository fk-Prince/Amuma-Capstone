import BaseService from '~/api/BaseService';

class MessageService extends BaseService {
    private static instance: MessageService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): MessageService {
        if (!MessageService.instance) {
            MessageService.instance = new MessageService();
        }
        return MessageService.instance;
    }

    async conversations(): Promise<any> {
        return await this.request(`${this.resource}/conversations`, 'GET');
    }

    async branchConversations(payload: object): Promise<any> {
        return await this.request(`${this.resource}/branch-conversations`, 'GET', payload);
    }

    async thread(payload: { conversation_id: number }): Promise<any> {
        return await this.request(`${this.resource}/thread`, 'GET', payload);
    }

    async staffConversations(payload: object): Promise<any> {
        return await this.request(`${this.resource}/staff-conversations`, 'GET', payload);
    }

    async colleagues(payload: object): Promise<any> {
        return await this.request(`${this.resource}/colleagues`, 'GET', payload);
    }

    async openStaff(payload: {
        branch_uuid: string;
        employee_id: number;
    }): Promise<any> {
        return await this.request(`${this.resource}/open-staff`, 'POST', payload);
    }

    async recipients(payload: object): Promise<any> {
        return await this.request(`${this.resource}/recipients`, 'GET', payload);
    }

    async open(payload: {
        branch_uuid: string;
        client_id: number;
    }): Promise<any> {
        return await this.request(`${this.resource}/open`, 'POST', payload);
    }

    async send(payload: {
        conversation_id?: number | null;
        patient_id?: number | null;
        body: string;
    }): Promise<any> {
        return await this.request(this.resource, 'POST', payload);
    }

    private get resource(): string {
        return `${this.getBackendApi}/api/messages`;
    }
}

export const messageService = MessageService.getInstance();
