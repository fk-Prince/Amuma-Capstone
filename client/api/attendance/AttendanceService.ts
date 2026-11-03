import BaseService from '~/api/BaseService';

class AdlAttendanceService extends BaseService {
    private static instance: AdlAttendanceService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): AdlAttendanceService {
        if (!AdlAttendanceService.instance) {
            AdlAttendanceService.instance = new AdlAttendanceService();
        }
        return AdlAttendanceService.instance;
    }

    async generateQr(payload: {}): Promise<any> {
        return await this.request(this.resource + '/qr', 'GET', payload);
    }
    async verifyQr(payload: { token: string; type: string }) {
        await this.request('/api/qr/verify', 'POST', payload);
    }

    async demoVerifyQr(payload: { token: string; type: string; employee_id: number }) {
        return await this.request(this.getBackendApi + '/api/qr/demo-verify', 'POST', payload);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/adl-attendances`;
    }
}

export const adlAttendanceService = AdlAttendanceService.getInstance();
