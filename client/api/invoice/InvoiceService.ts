

import BaseService from '~/api/BaseService';

class InvoiceService extends BaseService {
    private static instance: InvoiceService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): InvoiceService {
        if (!InvoiceService.instance) {
            InvoiceService.instance = new InvoiceService();
        }
        return InvoiceService.instance;
    }


    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }

    async create(payload: object): Promise<any> {
        return await this.request(this.resource, 'POST', payload);
    }

    async show(payload: {}, uuid: string): Promise<any> { //used 
        return await this.request(`${this.resource}/${uuid}`, 'GET', payload);
    }


    async overview(payload: {}): Promise<any> {
        return await this.request(`${this.resource}/overview`, 'GET', payload);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/invoices`;
    }
}

export const invoiceService = InvoiceService.getInstance();
