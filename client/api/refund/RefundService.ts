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

    async action(payload: object): Promise<any> {
        return await this.request(this.resource + '/action', 'POST', payload);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/refunds`;
    }
}

export const refundService = RefundService.getInstance();
