import BaseService from '~/api/BaseService';

class PatientAccessService extends BaseService {
    private static instance: PatientAccessService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): PatientAccessService {
        if (!PatientAccessService.instance) {
            PatientAccessService.instance = new PatientAccessService();
        }
        return PatientAccessService.instance;
    }


    async retrieveAction(params: object = {}): Promise<any> {
        return await this.request(this.resource + '/action', 'GET', params);
    }

    async executeAction(params: object = {}): Promise<any> {
        return await this.request(this.resource + '/action', 'POST', params);
    }




    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/patient-access`;
    }
}

export const patientAccessService = PatientAccessService.getInstance();
