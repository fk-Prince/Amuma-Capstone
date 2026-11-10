import BaseService from '~/api/BaseService';

class ReportService extends BaseService {
    private static instance: ReportService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): ReportService {
        if (!ReportService.instance) {
            ReportService.instance = new ReportService();
        }
        return ReportService.instance;
    }

    async sales(params: object = {}): Promise<any> {
        return await this.request(`${this.resource}/sales`, 'GET', params);
    }

    async salesSummary(params: object = {}): Promise<any> {
        return await this.request(`${this.resource}/sales/summary`, 'GET', params);
    }

    async salesExport(params: object = {}): Promise<any> {
        return await this.request(`${this.resource}/sales/export`, 'GET', params);
    }

    private get resource(): string {
        return `${this.getBackendApi}/api/reports`;
    }
}

export const reportService = ReportService.getInstance();
