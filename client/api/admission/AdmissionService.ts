import BaseService from '~/api/BaseService';

class AdmissionService extends BaseService {
    private static instance: AdmissionService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): AdmissionService {
        if (!AdmissionService.instance) {
            AdmissionService.instance = new AdmissionService();
        }
        return AdmissionService.instance;
    }


    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }

    async create(payload: object): Promise<any> { // USED
        return await this.request(this.resource, 'POST', payload);
    }

    async show(uuid: string): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, 'GET');
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
        return `${backend}/api/admissions`;
    }
}

export const admissionService = AdmissionService.getInstance();
