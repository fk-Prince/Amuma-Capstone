import BaseService from '~/api/BaseService';

class RefundService extends BaseService {
    private static instance: RefundService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): RefundService {
        if (!RefundService.instance) {
            RefundService.instance = new RefundService();
        }
        return RefundService.instance;
    }

    // amount is optional — omit it to claim the whole credit, or pass a smaller
    // figure to leave the rest sitting on the account.
    async claim(payload: {
        patient_id: number;
        method: string;
        account_details: string;
        amount?: number;
        reason?: string;
    }): Promise<any> {
        return await this.request(this.resource + '/action', 'POST', payload);
    }

    async issue(payload: {
        invoice_code: string;
        branch_uuid: string;
        amount?: number;
        method?: string;
        account_details?: string;
        reason?: string;
    }): Promise<any> {
        return await this.request(this.resource + '/issue', 'POST', payload);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/refunds`;
    }
}

export const refundService = RefundService.getInstance();
