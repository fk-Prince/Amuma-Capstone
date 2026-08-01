import BaseService from '~/api/BaseService';

class PatientService extends BaseService {
    private static instance: PatientService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): PatientService {
        if (!PatientService.instance) {
            PatientService.instance = new PatientService();
        }
        return PatientService.instance;
    }



    async list(params: object = {}): Promise<any> { // used
        return await this.request(this.resource, 'GET', params);
    }

    async create(payload: object): Promise<any> {
        return await this.request(this.resource, 'POST', payload);
    }

    async show(payload: object, uuid: string): Promise<any> { // used
        return await this.request(`${this.resource}/${uuid}`, 'GET', payload);
    }

    async update(uuid: string, payload: object): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, 'PUT', payload);
    }

    async delete(uuid: string): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, 'DELETE');
    }

    async restore(uuid: string): Promise<any> {
        return await this.request(`${this.resource}/${uuid}/restore`, 'POST');
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/patients`;
    }
}

export const patientService = PatientService.getInstance();
