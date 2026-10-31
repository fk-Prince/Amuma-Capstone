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

    async salesVat(params: object = {}): Promise<any> {
        return await this.request(`${this.resource}/sales-vat`, 'GET', params);
    }

    async salesVatSummary(params: object = {}): Promise<any> {
        return await this.request(`${this.resource}/sales-vat/summary`, 'GET', params);
    }

    async salesVatExport(params: object = {}): Promise<any> {
        return await this.request(`${this.resource}/sales-vat/export`, 'GET', params);
    }

    private get resource(): string {
        return `${this.getBackendApi}/api/reports`;
    }
}

export const reportService = ReportService.getInstance();
