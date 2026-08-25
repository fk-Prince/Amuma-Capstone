import BaseService from '~/api/BaseService';

class PatientActivityService extends BaseService {
    private static instance: PatientActivityService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): PatientActivityService {
        if (!PatientActivityService.instance) {
            PatientActivityService.instance = new PatientActivityService();
        }
        return PatientActivityService.instance;
    }


    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }

    async create(payload: object): Promise<any> {
        return await this.request(this.resource, 'POST', payload);
    }

    async update(uuid: string, payload: object): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, 'PUT', payload);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/patient-activities`;
    }
}

export const patientActivityService = PatientActivityService.getInstance();
