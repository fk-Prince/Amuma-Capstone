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

    async create(payload: object): Promise<any> { // USED
        return await this.request(this.resource, 'POST', payload);
    }

    async list(payload: object): Promise<any> {
        return await this.request(this.resource, "GET", payload);
    }

    async show(id: string, payload: object): Promise<any> {
        return await this.request(`${this.resource}/${id}`, "GET", payload);
    }

    async action(payload: object): Promise<any> { // USED
        return await this.request(this.resource + '/action', 'POST', payload);
    }

    // async admit(payload: object): Promise<any> { // USED
    //     return await this.request(this.resource + '/admit', 'POST', payload);
    // }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/admissions`;
    }
}

export const admissionService = AdmissionService.getInstance();
