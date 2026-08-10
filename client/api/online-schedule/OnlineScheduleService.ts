import BaseService from '~/api/BaseService';

class OnlineScheduleService extends BaseService {
    private static instance: OnlineScheduleService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): OnlineScheduleService {
        if (!OnlineScheduleService.instance) {
            OnlineScheduleService.instance = new OnlineScheduleService();
        }
        return OnlineScheduleService.instance;
    }

    async generateQr(payload: {}): Promise<any> {
        return await this.request(this.resource + '/qr', 'GET', payload);
    }
    async verifyQr(payload: { token: string; type: string }) {
        await this.request('/api/qr/verify', 'POST', payload);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/online-schedules`;
    }
}

export const onlineScheduleService = OnlineScheduleService.getInstance();
