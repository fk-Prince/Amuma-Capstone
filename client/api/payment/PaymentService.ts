import BaseService from '~/api/BaseService';

class PaymentService extends BaseService {
    private static instance: PaymentService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): PaymentService {
        if (!PaymentService.instance) {
            PaymentService.instance = new PaymentService();
        }
        return PaymentService.instance;
    }

    async pay(payload: {
        patient_id: number;
        amount: number;
        method: string;
        account_details: string;
    }): Promise<any> {
        return await this.request(this.resource + '/action', 'POST', payload);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/payments`;
    }
}

export const paymentService = PaymentService.getInstance();
